<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/deliverers')]
final class DelivererApiController extends AbstractController
{
    private const COURIER_ROLE = 'ROLE_COURIER';

    #[Route('', name: 'api_deliverer_list', methods: ['GET'])]
    public function list(UserRepository $userRepository): JsonResponse
    {
        $deliverers = array_map(
            fn (User $user) => $this->serializeDeliverer($user),
            $userRepository->findCouriers()
        );

        return $this->json(['data' => $deliverers], Response::HTTP_OK);
    }

    #[Route('', name: 'api_deliverer_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
        MailerInterface $mailer,
    ): JsonResponse {
        $payload = $this->getJsonPayload($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $deliverer = new User();
        if (\array_key_exists('email', $payload)) {
            $deliverer->setEmail((string) $payload['email']);
        }
        $deliverer->setRoles([self::COURIER_ROLE]);

        $plainPassword = $this->generatePassword();
        $deliverer->setPassword($passwordHasher->hashPassword($deliverer, $plainPassword));

        $errors = $validator->validate($deliverer);
        if (\count($errors) > 0) {
            return $this->json(['errors' => $this->normalizeViolations($errors)], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($deliverer);
        $entityManager->flush();

        $mailer->send(
            (new Email())
                ->from('noreply@citylunch.local')
                ->to($deliverer->getEmail() ?? '')
                ->subject('CityLunch - Vos identifiants livreur')
                ->text(
                    sprintf(
                        "Bonjour,\n\nVotre compte livreur a été créé.\n\nEmail: %s\nMot de passe: %s\n\nVous pouvez ensuite récupérer un token via POST /api/login.\n",
                        (string) $deliverer->getEmail(),
                        $plainPassword
                    )
                )
        );

        return $this->json(
            [
                'data' => $this->serializeDeliverer($deliverer),
                'generated_password' => $plainPassword,
            ],
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'api_deliverer_show', methods: ['GET'])]
    public function show(?User $deliverer): JsonResponse
    {
        if (!$deliverer || !$this->isCourier($deliverer)) {
            return $this->json(['error' => 'Deliverer not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['data' => $this->serializeDeliverer($deliverer)], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_deliverer_update', methods: ['PUT'])]
    public function update(
        ?User $deliverer,
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
    ): JsonResponse {
        if (!$deliverer || !$this->isCourier($deliverer)) {
            return $this->json(['error' => 'Deliverer not found'], Response::HTTP_NOT_FOUND);
        }

        $payload = $this->getJsonPayload($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        if (\array_key_exists('email', $payload)) {
            $deliverer->setEmail((string) $payload['email']);
        }

        $errors = $validator->validate($deliverer);
        if (\count($errors) > 0) {
            return $this->json(['errors' => $this->normalizeViolations($errors)], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->flush();

        return $this->json(['data' => $this->serializeDeliverer($deliverer)], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_deliverer_delete', methods: ['DELETE'])]
    public function delete(?User $deliverer, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$deliverer || !$this->isCourier($deliverer)) {
            return $this->json(['error' => 'Deliverer not found'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($deliverer);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    private function isCourier(User $user): bool
    {
        return \in_array(self::COURIER_ROLE, $user->getRoles(), true);
    }

    private function generatePassword(int $length = 16): string
    {
        $bytes = random_bytes($length);
        $password = rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');

        return substr($password, 0, $length);
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function getJsonPayload(Request $request): array|JsonResponse
    {
        $raw = $request->getContent();
        if ($raw === '') {
            return [];
        }

        try {
            $payload = json_decode($raw, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        if (!\is_array($payload)) {
            return $this->json(['error' => 'JSON body must be an object'], Response::HTTP_BAD_REQUEST);
        }

        return $payload;
    }

    /**
     * @param \Traversable<\Symfony\Component\Validator\ConstraintViolation> $violations
     * @return array<string, list<string>>
     */
    private function normalizeViolations(\Traversable $violations): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $field = (string) $violation->getPropertyPath();
            $errors[$field][] = (string) $violation->getMessage();
        }

        return $errors;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDeliverer(User $deliverer): array
    {
        return [
            'id' => $deliverer->getId(),
            'email' => $deliverer->getEmail(),
            'roles' => $deliverer->getRoles(),
        ];
    }
}


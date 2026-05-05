<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use App\Security\JwtTokenManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class AuthApiController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        JwtTokenManager $jwtTokenManager,
    ): JsonResponse {
        try {
            $payload = json_decode($request->getContent() ?: '{}', true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        if (!\is_array($payload)) {
            return $this->json(['error' => 'JSON body must be an object'], Response::HTTP_BAD_REQUEST);
        }

        $email = isset($payload['email']) ? (string) $payload['email'] : '';
        $password = isset($payload['password']) ? (string) $payload['password'] : '';
        if ($email === '' || $password === '') {
            return $this->json(['error' => 'Missing email or password'], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            return $this->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        if (!\in_array('ROLE_COURIER', $user->getRoles(), true)) {
            return $this->json(['error' => 'Only deliverers can login here'], Response::HTTP_FORBIDDEN);
        }

        if (!$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $jwtTokenManager->createToken($user);

        return $this->json(
            [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => $jwtTokenManager->getTtlSeconds(),
            ],
            Response::HTTP_OK
        );
    }
}


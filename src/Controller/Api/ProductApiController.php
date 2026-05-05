<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/products')]
final class ProductApiController extends AbstractController
{
    #[Route('', name: 'api_product_list', methods: ['GET'])]
    public function list(ProductRepository $productRepository): JsonResponse
    {
        $products = array_map(
            fn (Product $product) => $this->serializeProduct($product),
            $productRepository->findAll()
        );

        return $this->json(['data' => $products], Response::HTTP_OK);
    }

    #[Route('', name: 'api_product_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
    ): JsonResponse {
        $payload = $this->getJsonPayload($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $product = new Product();
        if (\array_key_exists('name', $payload)) {
            $product->setName((string) $payload['name']);
        }
        if (\array_key_exists('description', $payload)) {
            $product->setDescription($payload['description'] !== null ? (string) $payload['description'] : null);
        }
        if (\array_key_exists('price', $payload)) {
            $product->setPrice((float) $payload['price']);
        }
        if (\array_key_exists('image', $payload)) {
            $product->setImage($payload['image'] !== null ? (string) $payload['image'] : null);
        }

        $errors = $validator->validate($product);
        if (\count($errors) > 0) {
            return $this->json(['errors' => $this->normalizeViolations($errors)], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($product);
        $entityManager->flush();

        return $this->json(['data' => $this->serializeProduct($product)], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_product_show', methods: ['GET'])]
    public function show(?Product $product): JsonResponse
    {
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['data' => $this->serializeProduct($product)], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_product_update', methods: ['PUT'])]
    public function update(
        ?Product $product,
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
    ): JsonResponse {
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $payload = $this->getJsonPayload($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        if (\array_key_exists('name', $payload)) {
            $product->setName((string) $payload['name']);
        }
        if (\array_key_exists('description', $payload)) {
            $product->setDescription($payload['description'] !== null ? (string) $payload['description'] : null);
        }
        if (\array_key_exists('price', $payload)) {
            $product->setPrice((float) $payload['price']);
        }
        if (\array_key_exists('image', $payload)) {
            $product->setImage($payload['image'] !== null ? (string) $payload['image'] : null);
        }

        $errors = $validator->validate($product);
        if (\count($errors) > 0) {
            return $this->json(['errors' => $this->normalizeViolations($errors)], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->flush();

        return $this->json(['data' => $this->serializeProduct($product)], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_product_delete', methods: ['DELETE'])]
    public function delete(?Product $product, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($product);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
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
    private function serializeProduct(Product $product): array
    {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'image' => $product->getImage(),
        ];
    }
}


<?php

namespace App\Controller\Api;

use App\Entity\Bag;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\BagRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/bag')]
final class BagApiController extends AbstractController
{
    #[Route('', name: 'api_bag_show', methods: ['GET'])]
    public function show(BagRepository $bagRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $deliverer = $this->getAuthenticatedDeliverer();
        if ($deliverer instanceof JsonResponse) {
            return $deliverer;
        }

        $bag = $this->getOrCreateBag($deliverer, $bagRepository, $entityManager);

        return $this->json(['data' => $this->serializeBag($bag)], Response::HTTP_OK);
    }

    #[Route('/items', name: 'api_bag_add_item', methods: ['POST'])]
    public function addItem(
        Request $request,
        ProductRepository $productRepository,
        BagRepository $bagRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $deliverer = $this->getAuthenticatedDeliverer();
        if ($deliverer instanceof JsonResponse) {
            return $deliverer;
        }

        $payload = $this->getJsonPayload($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $productId = $payload['productId'] ?? null;
        $quantity = $payload['quantity'] ?? 1;
        if (!\is_int($productId) && !(\is_string($productId) && ctype_digit($productId))) {
            return $this->json(['error' => 'Missing productId'], Response::HTTP_BAD_REQUEST);
        }
        if (!\is_int($quantity) && !(\is_string($quantity) && ctype_digit($quantity))) {
            return $this->json(['error' => 'Invalid quantity'], Response::HTTP_BAD_REQUEST);
        }

        $product = $productRepository->find((int) $productId);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $bag = $this->getOrCreateBag($deliverer, $bagRepository, $entityManager);

        try {
            $bag->addProduct($product, (int) $quantity);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($bag);
        $entityManager->flush();

        return $this->json(['data' => $this->serializeBag($bag)], Response::HTTP_OK);
    }

    #[Route('/items/{productId}', name: 'api_bag_remove_item', methods: ['DELETE'])]
    public function removeItem(
        int $productId,
        Request $request,
        ProductRepository $productRepository,
        BagRepository $bagRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $deliverer = $this->getAuthenticatedDeliverer();
        if ($deliverer instanceof JsonResponse) {
            return $deliverer;
        }

        $product = $productRepository->find($productId);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $bag = $this->getOrCreateBag($deliverer, $bagRepository, $entityManager);

        $quantityParam = $request->query->get('quantity');
        $quantity = $quantityParam !== null ? (int) $quantityParam : $bag->getProductQuantity($product);
        if ($quantity <= 0) {
            return $this->json(['error' => 'Invalid quantity'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $bag->removeProduct($product, $quantity);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->flush();

        return $this->json(['data' => $this->serializeBag($bag)], Response::HTTP_OK);
    }

    private function getOrCreateBag(User $deliverer, BagRepository $bagRepository, EntityManagerInterface $entityManager): Bag
    {
        $bag = $bagRepository->findOneByDeliverer($deliverer);
        if ($bag) {
            return $bag;
        }

        $bag = (new Bag())->setDeliverer($deliverer);
        $entityManager->persist($bag);
        $entityManager->flush();

        return $bag;
    }

    /**
     * @return User|JsonResponse
     */
    private function getAuthenticatedDeliverer(): User|JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        return $user;
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
     * @return array<string, mixed>
     */
    private function serializeBag(Bag $bag): array
    {
        $items = [];
        foreach ($bag->getItems() as $item) {
            $product = $item->getProduct();
            if (!$product instanceof Product) {
                continue;
            }

            $items[] = [
                'product' => [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'price' => $product->getPrice(),
                ],
                'quantity' => $item->getQuantity(),
            ];
        }

        return [
            'id' => $bag->getId(),
            'deliverer_id' => $bag->getDeliverer()?->getId(),
            'items' => $items,
        ];
    }
}

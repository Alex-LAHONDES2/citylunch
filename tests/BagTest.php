<?php

namespace App\Tests;

use App\Entity\Bag;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;

final class BagTest extends TestCase
{
    public function testAddProductMergesQuantitiesForSameProductInstance(): void
    {
        $bag = new Bag();
        $product = (new Product())
            ->setName('P1')
            ->setPrice(1.0);

        $bag->addProduct($product, 1);
        $bag->addProduct($product, 2);

        self::assertSame(3, $bag->getProductQuantity($product));
        self::assertCount(1, $bag->getItems());
    }

    public function testAddProductRequiresPositiveQuantity(): void
    {
        $bag = new Bag();
        $product = (new Product())
            ->setName('P1')
            ->setPrice(1.0);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Quantity must be positive');

        $bag->addProduct($product, 0);
    }

    public function testRemoveProductRemovesLineWhenQuantityReachesZero(): void
    {
        $bag = new Bag();
        $product = (new Product())
            ->setName('P1')
            ->setPrice(1.0);

        $bag->addProduct($product, 2);
        $bag->removeProduct($product, 2);

        self::assertSame(0, $bag->getProductQuantity($product));
        self::assertCount(0, $bag->getItems());
    }

    public function testRemoveProductFailsWhenProductNotInBag(): void
    {
        $bag = new Bag();
        $product = (new Product())
            ->setName('P1')
            ->setPrice(1.0);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Product not found in bag');

        $bag->removeProduct($product, 1);
    }
}


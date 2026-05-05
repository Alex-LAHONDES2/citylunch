<?php

namespace App\Tests;

use App\Entity\Bag;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;

final class BagBusinessRulesTest extends TestCase
{
    public function testCannotRemoveMoreThanCurrentQuantity(): void
    {
        $bag = new Bag();
        $product = (new Product())
            ->setName('Test product')
            ->setPrice(10.0);

        $bag->addProduct($product, 2);

        try {
            $bag->removeProduct($product, 3);
            self::fail('Expected DomainException.');
        } catch (\DomainException $e) {
            self::assertSame('Cannot remove more than current quantity', $e->getMessage());
        }

        self::assertSame(2, $bag->getProductQuantity($product));
    }
}

<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $products = [
            ['Pizza Margherita', 'Tomate, mozzarella, basilic', 9.99],
            ['Burger Classic', 'Boeuf, cheddar, salade', 11.50],
            ['Tiramisu', 'Dessert italien maison', 5.90],
        ];

        foreach ($products as $p) {
            $product = new Product();
            $product->setName($p[0]);
            $product->setDescription($p[1]);
            $product->setPrice($p[2]);

            $manager->persist($product);
        }

        $manager->flush();
    }
}

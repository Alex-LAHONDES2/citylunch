<?php

namespace App\Entity;

use App\Repository\BagItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BagItemRepository::class)]
#[ORM\Table(name: 'bag_item')]
#[ORM\UniqueConstraint(name: 'UNIQ_BAG_ITEM_BAG_PRODUCT', columns: ['bag_id', 'product_id'])]
class BagItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'bag_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Bag $bag = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Product $product = null;

    #[ORM\Column]
    #[Assert\Positive]
    private int $quantity = 1;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBag(): ?Bag
    {
        return $this->bag;
    }

    public function setBag(Bag $bag): static
    {
        $this->bag = $bag;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }
}

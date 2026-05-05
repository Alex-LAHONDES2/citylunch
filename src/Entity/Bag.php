<?php

namespace App\Entity;

use App\Repository\BagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BagRepository::class)]
#[ORM\Table(name: 'bag')]
class Bag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $deliverer = null;

    /**
     * @var Collection<int, BagItem>
     */
    #[ORM\OneToMany(mappedBy: 'bag', targetEntity: BagItem::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDeliverer(): ?User
    {
        return $this->deliverer;
    }

    public function setDeliverer(User $deliverer): static
    {
        $this->deliverer = $deliverer;

        return $this;
    }

    /**
     * @return Collection<int, BagItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addProduct(Product $product, int $quantity): void
    {
        if ($quantity <= 0) {
            throw new \DomainException('Quantity must be positive');
        }

        foreach ($this->items as $item) {
            if ($item->getProduct() === $product) {
                $item->setQuantity($item->getQuantity() + $quantity);
                return;
            }
        }

        $item = (new BagItem())
            ->setBag($this)
            ->setProduct($product)
            ->setQuantity($quantity);

        $this->items->add($item);
    }

    public function getProductQuantity(Product $product): int
    {
        foreach ($this->items as $item) {
            if ($item->getProduct() === $product) {
                return $item->getQuantity();
            }
        }

        return 0;
    }

    public function removeProduct(Product $product, int $quantity): void
    {
        if ($quantity <= 0) {
            throw new \DomainException('Quantity must be positive');
        }

        foreach ($this->items as $item) {
            if ($item->getProduct() !== $product) {
                continue;
            }

            if ($quantity > $item->getQuantity()) {
                throw new \DomainException('Cannot remove more than current quantity');
            }

            $newQuantity = $item->getQuantity() - $quantity;
            if ($newQuantity === 0) {
                $this->items->removeElement($item);
            } else {
                $item->setQuantity($newQuantity);
            }

            return;
        }

        throw new \DomainException('Product not found in bag');
    }
}

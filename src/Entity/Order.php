<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OrderRepository::class)
 * @ORM\Table(name="`order`")
 */
class Order
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $priceTotal;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $itemsTotal;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPriceTotal(): ?float
    {
        return $this->priceTotal;
    }

    public function setPriceTotal(?float $priceTotal): self
    {
        $this->priceTotal = $priceTotal;

        return $this;
    }

    public function getItemsTotal(): ?int
    {
        return $this->itemsTotal;
    }

    public function setItemsTotal(?int $itemsTotal): self
    {
        $this->itemsTotal = $itemsTotal;

        return $this;
    }
}

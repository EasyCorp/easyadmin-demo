<?php

namespace App\Entity;

use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'customer_order')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    private ?string $number = null;

    #[ORM\Column(length: 20, enumType: OrderStatus::class)]
    private OrderStatus $status = OrderStatus::Pending;

    #[ORM\Column]
    private \DateTimeImmutable $placedAt;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $shippedOn = null;

    #[ORM\Column(length: 3)]
    private string $currency = 'EUR';

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $customer = null;

    /**
     * @var Collection<int, OrderLine>
     */
    #[ORM\OneToMany(targetEntity: OrderLine::class, mappedBy: 'order', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lines;

    public function __construct()
    {
        $this->placedAt = new \DateTimeImmutable();
        $this->lines = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->number;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPlacedAt(): \DateTimeImmutable
    {
        return $this->placedAt;
    }

    public function setPlacedAt(\DateTimeImmutable $placedAt): static
    {
        $this->placedAt = $placedAt;

        return $this;
    }

    public function getShippedOn(): ?\DateTimeImmutable
    {
        return $this->shippedOn;
    }

    public function setShippedOn(?\DateTimeImmutable $shippedOn): static
    {
        $this->shippedOn = $shippedOn;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @return Collection<int, OrderLine>
     */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function addLine(OrderLine $line): static
    {
        if (!$this->lines->contains($line)) {
            $this->lines->add($line);
            $line->setOrder($this);
        }

        return $this;
    }

    public function removeLine(OrderLine $line): static
    {
        if ($this->lines->removeElement($line) && $line->getOrder() === $this) {
            $line->setOrder(null);
        }

        return $this;
    }

    public function getTotal(): int
    {
        $total = 0;
        foreach ($this->lines as $line) {
            $total += $line->getSubtotal();
        }

        return $total;
    }

    public function isPending(): bool
    {
        return OrderStatus::Pending === $this->status;
    }

    public function isPaid(): bool
    {
        return OrderStatus::Paid === $this->status;
    }

    public function isShipped(): bool
    {
        return OrderStatus::Shipped === $this->status;
    }

    public function markAsPaid(): static
    {
        $this->status = OrderStatus::Paid;

        return $this;
    }

    public function markAsShipped(): static
    {
        $this->status = OrderStatus::Shipped;
        $this->shippedOn = new \DateTimeImmutable();

        return $this;
    }

    public function markAsDelivered(): static
    {
        $this->status = OrderStatus::Delivered;

        return $this;
    }

    public function cancel(): static
    {
        $this->status = OrderStatus::Cancelled;

        return $this;
    }
}

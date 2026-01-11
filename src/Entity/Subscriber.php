<?php

namespace App\Entity;

use App\Enum\SubscriberSource;
use App\Repository\SubscriberRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriberRepository::class)]
class Subscriber
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $name = null;

    #[ORM\Column]
    private \DateTimeImmutable $subscribedAt;

    #[ORM\Column]
    private bool $isConfirmed = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

    #[ORM\Column(length: 20, enumType: SubscriberSource::class)]
    private SubscriberSource $source = SubscriberSource::Homepage;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $unsubscribedAt = null;

    #[ORM\Column(length: 5)]
    private string $locale = 'en';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress = null;

    public function __construct()
    {
        $this->subscribedAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->email ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSubscribedAt(): \DateTimeImmutable
    {
        return $this->subscribedAt;
    }

    public function setSubscribedAt(\DateTimeImmutable $subscribedAt): static
    {
        $this->subscribedAt = $subscribedAt;

        return $this;
    }

    public function isConfirmed(): bool
    {
        return $this->isConfirmed;
    }

    public function setIsConfirmed(bool $isConfirmed): static
    {
        $this->isConfirmed = $isConfirmed;

        return $this;
    }

    public function getConfirmedAt(): ?\DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function setConfirmedAt(?\DateTimeImmutable $confirmedAt): static
    {
        $this->confirmedAt = $confirmedAt;

        return $this;
    }

    public function getSource(): SubscriberSource
    {
        return $this->source;
    }

    public function setSource(SubscriberSource $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getUnsubscribedAt(): ?\DateTimeImmutable
    {
        return $this->unsubscribedAt;
    }

    public function setUnsubscribedAt(?\DateTimeImmutable $unsubscribedAt): static
    {
        $this->unsubscribedAt = $unsubscribedAt;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isConfirmed && null === $this->unsubscribedAt;
    }

    public function confirm(): static
    {
        $this->isConfirmed = true;
        $this->confirmedAt = new \DateTimeImmutable();

        return $this;
    }

    public function unsubscribe(): static
    {
        $this->unsubscribedAt = new \DateTimeImmutable();

        return $this;
    }
}

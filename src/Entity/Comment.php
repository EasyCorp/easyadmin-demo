<?php

namespace App\Entity;

use App\Enum\CommentStatus;
use App\Repository\CommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(length: 20, enumType: CommentStatus::class)]
    private CommentStatus $status = CommentStatus::Pending;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Post $post = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): static
    {
        $this->post = $post;

        return $this;
    }

    public function getStatus(): CommentStatus
    {
        return $this->status;
    }

    public function setStatus(CommentStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isApproved(): bool
    {
        return CommentStatus::Approved === $this->status;
    }

    public function isPending(): bool
    {
        return CommentStatus::Pending === $this->status;
    }

    public function isSpam(): bool
    {
        return CommentStatus::Spam === $this->status;
    }

    public function approve(): static
    {
        $this->status = CommentStatus::Approved;

        return $this;
    }

    public function markAsSpam(): static
    {
        $this->status = CommentStatus::Spam;

        return $this;
    }

    public function reject(): static
    {
        $this->status = CommentStatus::Rejected;

        return $this;
    }
}

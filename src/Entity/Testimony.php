<?php

namespace App\Entity;

use App\Repository\TestimonyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TestimonyRepository::class)]
class Testimony
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $authorName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $authorRole = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $text = null;

    #[ORM\Column]
    private ?int $rating = 5;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?bool $active = true;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarText = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthorName(): ?string
    {
        return $this->authorName;
    }

    public function setAuthorName(string $authorName): static
    {
        $this->authorName = $authorName;
        // Simple avatar text generator if needed
        if (!$this->avatarText) {
             $parts = explode(' ', $authorName);
             $initials = '';
             foreach ($parts as $part) {
                 $initials .= strtoupper(substr($part, 0, 1));
                 if (strlen($initials) >= 2) break;
             }
             $this->avatarText = $initials;
        }

        return $this;
    }

    public function getAuthorRole(): ?string
    {
        return $this->authorRole;
    }

    public function setAuthorRole(?string $authorRole): static
    {
        $this->authorRole = $authorRole;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getAvatarText(): ?string
    {
        return $this->avatarText;
    }

    public function setAvatarText(?string $avatarText): static
    {
        $this->avatarText = $avatarText;

        return $this;
    }
}

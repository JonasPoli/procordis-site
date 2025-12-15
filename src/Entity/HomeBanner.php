<?php

namespace App\Entity;

use App\Repository\HomeBannerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: HomeBannerRepository::class)]
#[Vich\Uploadable]
class HomeBanner
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subtitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    #[Vich\UploadableField(mapping: 'banner_image', fileNameProperty: 'imageName')]
    private ?File $imageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $btn1Text = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $btn1Link = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $btn2Text = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $btn2Link = null;

    #[ORM\Column]
    private ?int $sortOrder = 0;

    #[ORM\Column]
    private ?bool $active = true;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setSubtitle(?string $subtitle): static
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): static
    {
        $this->imageName = $imageName;

        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;
        if (null !== $imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getBtn1Text(): ?string
    {
        return $this->btn1Text;
    }

    public function setBtn1Text(?string $btn1Text): static
    {
        $this->btn1Text = $btn1Text;

        return $this;
    }

    public function getBtn1Link(): ?string
    {
        return $this->btn1Link;
    }

    public function setBtn1Link(?string $btn1Link): static
    {
        $this->btn1Link = $btn1Link;

        return $this;
    }

    public function getBtn2Text(): ?string
    {
        return $this->btn2Text;
    }

    public function setBtn2Text(?string $btn2Text): static
    {
        $this->btn2Text = $btn2Text;

        return $this;
    }

    public function getBtn2Link(): ?string
    {
        return $this->btn2Link;
    }

    public function setBtn2Link(?string $btn2Link): static
    {
        $this->btn2Link = $btn2Link;

        return $this;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}

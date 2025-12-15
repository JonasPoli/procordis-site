<?php

namespace App\Entity;

use App\Repository\AboutPageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: AboutPageRepository::class)]
#[Vich\Uploadable]
class AboutPage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $homeTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $homeSummary = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $homeImageName = null;

    #[Vich\UploadableField(mapping: 'about_image', fileNameProperty: 'homeImageName')]
    private ?File $homeImageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mainTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $mainContent = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $vision = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $mission = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $ourValues = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHomeTitle(): ?string
    {
        return $this->homeTitle;
    }

    public function setHomeTitle(?string $homeTitle): static
    {
        $this->homeTitle = $homeTitle;

        return $this;
    }

    public function getHomeSummary(): ?string
    {
        return $this->homeSummary;
    }

    public function setHomeSummary(?string $homeSummary): static
    {
        $this->homeSummary = $homeSummary;

        return $this;
    }

    public function getHomeImageName(): ?string
    {
        return $this->homeImageName;
    }

    public function setHomeImageName(?string $homeImageName): static
    {
        $this->homeImageName = $homeImageName;

        return $this;
    }

    public function getHomeImageFile(): ?File
    {
        return $this->homeImageFile;
    }

    public function setHomeImageFile(?File $homeImageFile = null): void
    {
        $this->homeImageFile = $homeImageFile;
        if (null !== $homeImageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getMainTitle(): ?string
    {
        return $this->mainTitle;
    }

    public function setMainTitle(?string $mainTitle): static
    {
        $this->mainTitle = $mainTitle;

        return $this;
    }

    public function getMainContent(): ?string
    {
        return $this->mainContent;
    }

    public function setMainContent(?string $mainContent): static
    {
        $this->mainContent = $mainContent;

        return $this;
    }

    public function getVision(): ?string
    {
        return $this->vision;
    }

    public function setVision(?string $vision): static
    {
        $this->vision = $vision;

        return $this;
    }

    public function getMission(): ?string
    {
        return $this->mission;
    }

    public function setMission(?string $mission): static
    {
        $this->mission = $mission;

        return $this;
    }

    public function getOurValues(): ?string
    {
        return $this->ourValues;
    }

    public function setOurValues(?string $ourValues): static
    {
        $this->ourValues = $ourValues;

        return $this;
    }
}

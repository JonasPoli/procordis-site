<?php

namespace App\Entity;

use App\Repository\PageSeoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PageSeoRepository::class)]
class PageSeo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $homePageTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $homePageDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $aboutPageTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $aboutPageDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $servicesPageTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $servicesPageDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $newsPageTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $newsPageDescription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactPageTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contactPageDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $headerEndCode = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bodyStartCode = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bodyEndCode = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHomePageTitle(): ?string
    {
        return $this->homePageTitle;
    }

    public function setHomePageTitle(?string $homePageTitle): static
    {
        $this->homePageTitle = $homePageTitle;

        return $this;
    }

    public function getHomePageDescription(): ?string
    {
        return $this->homePageDescription;
    }

    public function setHomePageDescription(?string $homePageDescription): static
    {
        $this->homePageDescription = $homePageDescription;

        return $this;
    }

    public function getAboutPageTitle(): ?string
    {
        return $this->aboutPageTitle;
    }

    public function setAboutPageTitle(?string $aboutPageTitle): static
    {
        $this->aboutPageTitle = $aboutPageTitle;

        return $this;
    }

    public function getAboutPageDescription(): ?string
    {
        return $this->aboutPageDescription;
    }

    public function setAboutPageDescription(?string $aboutPageDescription): static
    {
        $this->aboutPageDescription = $aboutPageDescription;

        return $this;
    }

    public function getServicesPageTitle(): ?string
    {
        return $this->servicesPageTitle;
    }

    public function setServicesPageTitle(?string $servicesPageTitle): static
    {
        $this->servicesPageTitle = $servicesPageTitle;

        return $this;
    }

    public function getServicesPageDescription(): ?string
    {
        return $this->servicesPageDescription;
    }

    public function setServicesPageDescription(?string $servicesPageDescription): static
    {
        $this->servicesPageDescription = $servicesPageDescription;

        return $this;
    }

    public function getNewsPageTitle(): ?string
    {
        return $this->newsPageTitle;
    }

    public function setNewsPageTitle(?string $newsPageTitle): static
    {
        $this->newsPageTitle = $newsPageTitle;

        return $this;
    }

    public function getNewsPageDescription(): ?string
    {
        return $this->newsPageDescription;
    }

    public function setNewsPageDescription(?string $newsPageDescription): static
    {
        $this->newsPageDescription = $newsPageDescription;

        return $this;
    }

    public function getContactPageTitle(): ?string
    {
        return $this->contactPageTitle;
    }

    public function setContactPageTitle(?string $contactPageTitle): static
    {
        $this->contactPageTitle = $contactPageTitle;

        return $this;
    }

    public function getContactPageDescription(): ?string
    {
        return $this->contactPageDescription;
    }

    public function setContactPageDescription(?string $contactPageDescription): static
    {
        $this->contactPageDescription = $contactPageDescription;

        return $this;
    }

    public function getHeaderEndCode(): ?string
    {
        return $this->headerEndCode;
    }

    public function setHeaderEndCode(?string $headerEndCode): static
    {
        $this->headerEndCode = $headerEndCode;

        return $this;
    }

    public function getBodyStartCode(): ?string
    {
        return $this->bodyStartCode;
    }

    public function setBodyStartCode(?string $bodyStartCode): static
    {
        $this->bodyStartCode = $bodyStartCode;

        return $this;
    }

    public function getBodyEndCode(): ?string
    {
        return $this->bodyEndCode;
    }

    public function setBodyEndCode(?string $bodyEndCode): static
    {
        $this->bodyEndCode = $bodyEndCode;

        return $this;
    }
}

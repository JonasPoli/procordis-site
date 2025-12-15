<?php

namespace App\Entity;

use App\Repository\NewsCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NewsCategoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class NewsCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column]
    private ?bool $active = null;

    #[ORM\ManyToMany(targetEntity: News::class, mappedBy: 'categories')]
    private Collection $news;

    public function __construct()
    {
        $this->news = new ArrayCollection();
        $this->active = true;
    }

    #[ORM\PrePersist]
    public function computeSlug(): void
    {
        if (!$this->slug || '-' === $this->slug) {
            $slugger = new \Symfony\Component\String\Slugger\AsciiSlugger();
            $this->slug = strtolower($slugger->slug((string) $this->title));
        }
    }

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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

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

    /**
     * @return Collection<int, News>
     */
    public function getNews(): Collection
    {
        return $this->news;
    }

    public function addNews(News $news): static
    {
        if (!$this->news->contains($news)) {
            $this->news->add($news);
            $news->addCategory($this);
        }

        return $this;
    }

    public function removeNews(News $news): static
    {
        if ($this->news->removeElement($news)) {
            $news->removeCategory($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->title ?? '';
    }
}

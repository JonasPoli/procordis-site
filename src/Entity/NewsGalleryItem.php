<?php

namespace App\Entity;

use App\Repository\NewsGalleryItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Item da galeria de uma notícia: imagem enviada ou vídeo do YouTube.
 *
 * As versões geradas (thumb, medium, large) ficam em $files, com caminho relativo à pasta
 * da galeria (ver GalleryStorage) e dimensões reais em pixels:
 *   ['thumb' => ['path' => '12/foto-a1b2-thumb.webp', 'width' => 400, 'height' => 267], ...]
 * Vídeos têm apenas thumb e medium (capa baixada do YouTube).
 */
#[ORM\Entity(repositoryClass: NewsGalleryItemRepository::class)]
#[ORM\Table(name: 'news_gallery_item')]
#[ORM\Index(name: 'idx_news_gallery_item_position', columns: ['news_id', 'position'])]
#[ORM\HasLifecycleCallbacks]
class NewsGalleryItem
{
    public const CAPTION_MAX_LENGTH = 500;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'galleryItems')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?News $news = null;

    #[ORM\Column(length: 16, enumType: NewsGalleryItemType::class)]
    private NewsGalleryItemType $type;

    #[ORM\Column(length: self::CAPTION_MAX_LENGTH, nullable: true)]
    private ?string $caption = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    /** Item inativo continua no painel, mas não aparece no slideshow nem no lightbox públicos. */
    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    #[ORM\Column(length: 11, nullable: true)]
    private ?string $youtubeId = null;

    /**
     * @var array<string, array{path: string, width: int, height: int}>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $files = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $originalFilename = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(NewsGalleryItemType $type)
    {
        $this->type = $type;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNews(): ?News
    {
        return $this->news;
    }

    public function setNews(?News $news): static
    {
        $this->news = $news;

        return $this;
    }

    public function getType(): NewsGalleryItemType
    {
        return $this->type;
    }

    public function isImage(): bool
    {
        return NewsGalleryItemType::Image === $this->type;
    }

    public function isVideo(): bool
    {
        return NewsGalleryItemType::YouTube === $this->type;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function setCaption(?string $caption): static
    {
        $caption = null === $caption ? null : trim(preg_replace('/\s+/u', ' ', $caption) ?? '');
        $this->caption = '' === $caption ? null : mb_substr((string) $caption, 0, self::CAPTION_MAX_LENGTH);

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getYoutubeId(): ?string
    {
        return $this->youtubeId;
    }

    public function setYoutubeId(?string $youtubeId): static
    {
        $this->youtubeId = $youtubeId;

        return $this;
    }

    public function getYoutubeWatchUrl(): ?string
    {
        return $this->youtubeId ? 'https://www.youtube.com/watch?v='.$this->youtubeId : null;
    }

    /**
     * Embed em modo de privacidade aprimorada (youtube-nocookie.com).
     */
    public function getYoutubeEmbedUrl(): ?string
    {
        return $this->youtubeId ? 'https://www.youtube-nocookie.com/embed/'.$this->youtubeId : null;
    }

    /**
     * @return array<string, array{path: string, width: int, height: int}>
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * @param array<string, array{path: string, width: int, height: int}> $files
     */
    public function setFiles(array $files): static
    {
        $this->files = $files;

        return $this;
    }

    /**
     * @return array{path: string, width: int, height: int}|null
     */
    public function getVariant(string $name): ?array
    {
        return $this->files[$name] ?? null;
    }

    /**
     * Versão para ampliar/baixar: a grande nas imagens; a média (capa) nos vídeos.
     *
     * @return array{path: string, width: int, height: int}|null
     */
    public function getLargestVariant(): ?array
    {
        return $this->getVariant('large') ?? $this->getVariant('medium') ?? $this->getVariant('thumb');
    }

    /**
     * Caminhos relativos de todos os arquivos gerados (para exclusão).
     *
     * @return list<string>
     */
    public function getFilePaths(): array
    {
        return array_values(array_filter(array_map(
            static fn (array $variant): ?string => $variant['path'] ?? null,
            $this->files
        )));
    }

    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(?string $originalFilename): static
    {
        $this->originalFilename = null === $originalFilename ? null : mb_substr($originalFilename, 0, 255);

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}

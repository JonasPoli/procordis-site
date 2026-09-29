<?php

namespace App\Twig;

use App\Entity\NewsGalleryItem;
use App\Service\Gallery\GalleryStorage;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * news_gallery_url(item, 'thumb'|'medium'|'large') → URL pública da versão gerada (ou null).
 * news_gallery_srcset(item) → "…-thumb.webp 400w, …-medium.webp 1200w" (versões disponíveis até a média).
 */
final class NewsGalleryExtension extends AbstractExtension
{
    public function __construct(private readonly GalleryStorage $storage)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('news_gallery_url', $this->url(...)),
            new TwigFunction('news_gallery_srcset', $this->srcset(...)),
        ];
    }

    public function url(NewsGalleryItem $item, string $variant): ?string
    {
        $file = $item->getVariant($variant);

        return $file ? $this->storage->publicUrl($file['path']) : null;
    }

    /**
     * @param list<string> $variants
     */
    public function srcset(NewsGalleryItem $item, array $variants = ['thumb', 'medium']): string
    {
        $candidates = [];

        foreach ($variants as $variant) {
            $file = $item->getVariant($variant);
            if ($file) {
                $candidates[$file['width']] = $this->storage->publicUrl($file['path']).' '.$file['width'].'w';
            }
        }

        ksort($candidates);

        return implode(', ', $candidates);
    }
}

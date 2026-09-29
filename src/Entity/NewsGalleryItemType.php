<?php

namespace App\Entity;

/**
 * Tipos de item da galeria de uma notícia.
 */
enum NewsGalleryItemType: string
{
    case Image = 'image';
    case YouTube = 'youtube';

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Imagem',
            self::YouTube => 'Vídeo do YouTube',
        };
    }
}

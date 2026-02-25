<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TextExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('truncate_words', [$this, 'truncateWords']),
        ];
    }

    public function truncateWords(string $text, int $length = 250, string $ellipsis = '...'): string
    {
        // First remove any HTML tags, then decode HTML entities (like &ccedil;) so we count actual characters
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        // Substring up to the length
        $truncated = mb_substr($text, 0, $length);

        // Find the last space position
        $lastSpace = mb_strrpos($truncated, ' ');
        if ($lastSpace !== false) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }

        return $truncated . $ellipsis;
    }
}

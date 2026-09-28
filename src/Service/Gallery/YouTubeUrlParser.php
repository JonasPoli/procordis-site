<?php

namespace App\Service\Gallery;

/**
 * Reconhece o ID de um vídeo do YouTube a partir dos vários formatos de link.
 *
 * Aceita, com ou sem "https://", "www." ou "m.", e com parâmetros extras (&t=, &list=, ?si=...):
 *   youtube.com/watch?v=ID       youtu.be/ID            youtube.com/shorts/ID
 *   youtube.com/embed/ID         youtube.com/live/ID    youtube.com/v/ID
 *   m.youtube.com/watch?v=ID     music.youtube.com/watch?v=ID
 *   youtube-nocookie.com/embed/ID
 *
 * A mesma regra existe no JavaScript do painel (assets/admin/youtube.js) para a pré-visualização
 * instantânea; o servidor sempre valida de novo.
 */
final class YouTubeUrlParser
{
    public const ID_PATTERN = '/^[A-Za-z0-9_-]{11}$/';

    private const HOSTS = ['youtube.com', 'youtube-nocookie.com', 'youtu.be'];

    public static function extractId(string $input): ?string
    {
        $input = trim($input);

        if ('' === $input || preg_match('/\s/', $input)) {
            return null;
        }

        if (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $input)) {
            $input = 'https://'.ltrim($input, '/');
        }

        $parts = parse_url($input);
        if (false === $parts || empty($parts['host']) || !\in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return null;
        }

        $host = (string) preg_replace('/^(?:www\.|m\.|music\.)/', '', strtolower($parts['host']));
        if (!\in_array($host, self::HOSTS, true)) {
            return null;
        }

        $path = rtrim($parts['path'] ?? '', '/');
        parse_str($parts['query'] ?? '', $query);
        $candidate = null;

        if ('youtu.be' === $host) {
            $candidate = explode('/', ltrim($path, '/'))[0] ?? null;
        } elseif (preg_match('#^/(?:embed|shorts|live|v|e)/([^/]+)#', $path, $matches)) {
            $candidate = $matches[1];
        } elseif (\in_array($path, ['/watch', ''], true) && isset($query['v']) && \is_string($query['v'])) {
            $candidate = $query['v'];
        }

        return \is_string($candidate) && preg_match(self::ID_PATTERN, $candidate) ? $candidate : null;
    }

    /**
     * Separa um texto com vários links (um por linha, ou separados por espaço) e extrai os IDs.
     * Links repetidos são ignorados.
     *
     * @return list<array{input: string, videoId: ?string}>
     */
    public static function parseMany(string $text): array
    {
        $results = [];
        $seen = [];

        foreach (preg_split('/[\s,;]+/u', $text, -1, \PREG_SPLIT_NO_EMPTY) ?: [] as $line) {
            $videoId = self::extractId($line);
            $key = $videoId ?? 'invalid:'.$line;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $results[] = ['input' => $line, 'videoId' => $videoId];
        }

        return $results;
    }

    public static function watchUrl(string $videoId): string
    {
        return 'https://www.youtube.com/watch?v='.$videoId;
    }
}

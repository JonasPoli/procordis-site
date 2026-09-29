<?php

namespace App\Tests\Support;

use App\Service\Gallery\YouTubeUrlParser;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Simula o YouTube nos testes (configurado em framework.http_client.mock_response_factory, ambiente test).
 *
 * IDs especiais (11 caracteres):
 *  - PRIVATEvid1: oEmbed 401 (vídeo privado / incorporação desativada)
 *  - MISSINGvid1: oEmbed 404 (vídeo inexistente)
 *  - NOMAXRESvid: maxresdefault 404 → usa sddefault (640x480)
 *  - GRAYMAXRES1: maxresdefault devolve a imagem cinza 120x90 → usa sddefault
 *  - LETTERBOXv1: sem maxres; sddefault 4:3 com faixas pretas → recortada para 16:9
 *  Qualquer outro ID: vídeo público com capa maxres 1280x720.
 */
final class YouTubeMockResponseFactory
{
    /** @var list<string> URLs requisitadas (para asserções) */
    public static array $requests = [];

    public function __invoke(string $method, string $url, array $options = []): ResponseInterface
    {
        self::$requests[] = $url;

        if (str_starts_with($url, 'https://www.youtube.com/oembed')) {
            parse_str((string) parse_url($url, \PHP_URL_QUERY), $query);
            $videoId = YouTubeUrlParser::extractId((string) ($query['url'] ?? ''));

            return match ($videoId) {
                'PRIVATEvid1' => new MockResponse('Unauthorized', ['http_code' => 401]),
                'MISSINGvid1' => new MockResponse('Not Found', ['http_code' => 404]),
                default => new MockResponse(
                    json_encode(['title' => 'Vídeo de teste '.$videoId, 'author_name' => 'Procordis'], \JSON_THROW_ON_ERROR),
                    ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']]
                ),
            };
        }

        if (preg_match('#^https://i\.ytimg\.com/vi/([A-Za-z0-9_-]{11})/([a-z]+)\.jpg$#', $url, $matches)) {
            [, $videoId, $name] = $matches;

            if ('maxresdefault' === $name && \in_array($videoId, ['NOMAXRESvid', 'LETTERBOXv1'], true)) {
                return new MockResponse('', ['http_code' => 404]);
            }

            if ('maxresdefault' === $name && 'GRAYMAXRES1' === $videoId) {
                return new MockResponse(TestImageFactory::jpeg(120, 90, [200, 200, 200]), ['http_code' => 200]);
            }

            if ('sddefault' === $name && 'LETTERBOXv1' === $videoId) {
                return new MockResponse(TestImageFactory::letterboxedJpeg(640, 480), ['http_code' => 200]);
            }

            return match ($name) {
                'maxresdefault' => new MockResponse(TestImageFactory::jpeg(1280, 720), ['http_code' => 200]),
                'sddefault' => new MockResponse(TestImageFactory::jpeg(640, 480), ['http_code' => 200]),
                default => new MockResponse(TestImageFactory::jpeg(480, 360), ['http_code' => 200]),
            };
        }

        return new MockResponse('', ['http_code' => 404]);
    }
}

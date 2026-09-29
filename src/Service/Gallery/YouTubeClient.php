<?php

namespace App\Service\Gallery;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Consulta pública ao YouTube, sem chave de API:
 * - título pelo oEmbed (https://www.youtube.com/oembed?url=...&format=json);
 * - capa em i.ytimg.com, da maior para a menor (maxres → sd → hq).
 *
 * O servidor precisa de acesso de saída HTTPS a www.youtube.com e i.ytimg.com.
 */
class YouTubeClient
{
    private const OEMBED_URL = 'https://www.youtube.com/oembed';

    private const THUMBNAIL_URL = 'https://i.ytimg.com/vi/%s/%s.jpg';

    /** Da melhor para a pior. A maxres nem sempre existe (404 ou imagem cinza 120x90). */
    private const THUMBNAIL_NAMES = ['maxresdefault', 'sddefault', 'hqdefault'];

    /** A imagem genérica "sem capa" do YouTube tem 120x90. */
    private const PLACEHOLDER_MAX_WIDTH = 120;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{title: string, authorName: ?string}
     *
     * @throws GalleryException com mensagem amigável (privado, inexistente, falha de rede...)
     */
    public function fetchOEmbed(string $videoId): array
    {
        try {
            $response = $this->httpClient->request('GET', self::OEMBED_URL, [
                'query' => ['url' => YouTubeUrlParser::watchUrl($videoId), 'format' => 'json'],
                'timeout' => 8,
                'headers' => ['Accept' => 'application/json', 'Accept-Language' => 'pt-BR,pt;q=0.9'],
            ]);
            $status = $response->getStatusCode();
        } catch (HttpClientExceptionInterface $e) {
            $this->logger->warning('Falha ao consultar oEmbed do YouTube: '.$e->getMessage(), ['videoId' => $videoId]);

            throw new GalleryException('Não foi possível consultar o YouTube agora. Tente novamente em instantes.');
        }

        if (401 === $status || 403 === $status) {
            throw new GalleryException('Este vídeo é privado ou o dono não permite que ele seja exibido em outros sites.');
        }

        if (400 === $status || 404 === $status) {
            throw new GalleryException('Vídeo não encontrado. Confira o link: o vídeo pode ter sido removido ou estar privado.');
        }

        if (200 !== $status) {
            throw new GalleryException(sprintf('O YouTube não respondeu como esperado (código %d). Tente novamente em instantes.', $status));
        }

        try {
            $data = $response->toArray(false);
        } catch (HttpClientExceptionInterface) {
            throw new GalleryException('Resposta inválida do YouTube. Tente novamente em instantes.');
        }

        $title = trim((string) ($data['title'] ?? ''));

        return [
            'title' => '' !== $title ? $title : 'Vídeo do YouTube',
            'authorName' => isset($data['author_name']) ? (string) $data['author_name'] : null,
        ];
    }

    /**
     * Baixa a melhor capa disponível para um arquivo temporário (quem chama deve apagá-lo).
     *
     * @throws GalleryException se nenhuma capa válida for encontrada
     */
    public function downloadThumbnail(string $videoId): string
    {
        foreach (self::THUMBNAIL_NAMES as $name) {
            $url = sprintf(self::THUMBNAIL_URL, rawurlencode($videoId), $name);

            try {
                $response = $this->httpClient->request('GET', $url, ['timeout' => 10]);

                if (200 !== $response->getStatusCode()) {
                    continue;
                }

                $content = $response->getContent();
            } catch (HttpClientExceptionInterface $e) {
                $this->logger->info('Capa do YouTube indisponível: '.$e->getMessage(), ['url' => $url]);

                continue;
            }

            $size = @getimagesizefromstring($content);
            if (false === $size || $size[0] <= self::PLACEHOLDER_MAX_WIDTH) {
                continue; // 404 disfarçado: imagem cinza 120x90
            }

            $tmp = tempnam(sys_get_temp_dir(), 'yt_thumb_');
            if (false === $tmp || false === file_put_contents($tmp, $content)) {
                throw new GalleryException('Não foi possível salvar a capa do vídeo temporariamente no servidor.');
            }

            return $tmp;
        }

        throw new GalleryException('Não foi possível obter a capa deste vídeo no YouTube.');
    }
}

<?php

namespace App\Service\Gallery;

use App\Entity\News;
use App\Entity\NewsGalleryItem;
use App\Entity\NewsGalleryItemType;
use App\Repository\NewsGalleryItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Regras de negócio da galeria das notícias (usado pelo painel).
 */
class NewsGalleryManager
{
    public const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'JPG',
        'image/png' => 'PNG',
        'image/webp' => 'WebP',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NewsGalleryItemRepository $repository,
        private readonly GalleryStorage $storage,
        private readonly GalleryImageProcessor $imageProcessor,
        private readonly YouTubeClient $youTubeClient,
        private readonly SluggerInterface $slugger,
        #[Autowire('%app.news_gallery.max_upload_bytes%')]
        private readonly int $maxUploadBytes,
    ) {
    }

    /**
     * Limite efetivo por arquivo: o menor entre o da aplicação e o do PHP (upload_max_filesize/post_max_size).
     */
    public function getMaxUploadBytes(): int
    {
        $phpLimit = (int) UploadedFile::getMaxFilesize();

        return $phpLimit > 0 ? min($this->maxUploadBytes, $phpLimit) : $this->maxUploadBytes;
    }

    public function addImage(News $news, UploadedFile $file): NewsGalleryItem
    {
        $this->validateUpload($file);

        $mimeType = (string) $file->getMimeType();
        $originalName = $file->getClientOriginalName();
        $baseName = $this->uniqueBaseName(pathinfo($originalName, \PATHINFO_FILENAME) ?: 'imagem');

        $absoluteDir = $this->storage->prepareDirFor($news);
        $files = $this->imageProcessor->process(
            $file->getPathname(),
            $absoluteDir,
            $this->storage->relativeDirFor($news),
            $baseName,
            ['thumb', 'medium', 'large'],
            $mimeType,
        );

        $item = (new NewsGalleryItem(NewsGalleryItemType::Image))
            ->setFiles($files)
            ->setOriginalFilename($originalName)
            ->setPosition($this->repository->getNextPosition($news));

        return $this->persistNewItem($news, $item);
    }

    /**
     * Pré-visualização dos links colados (sem gravar nada): ID, título e capa de cada vídeo.
     *
     * @param list<string> $inputs
     *
     * @return list<array{input: string, videoId: ?string, title: ?string, thumbnailUrl: ?string, duplicate: bool, error: ?string}>
     */
    public function previewYouTube(News $news, array $inputs): array
    {
        $results = [];

        foreach (YouTubeUrlParser::parseMany(implode("\n", $inputs)) as $parsed) {
            $result = [
                'input' => $parsed['input'],
                'videoId' => $parsed['videoId'],
                'title' => null,
                'thumbnailUrl' => null,
                'duplicate' => false,
                'error' => null,
            ];

            if (null === $parsed['videoId']) {
                $result['error'] = 'Link do YouTube não reconhecido.';
                $results[] = $result;

                continue;
            }

            $result['thumbnailUrl'] = sprintf('https://i.ytimg.com/vi/%s/hqdefault.jpg', $parsed['videoId']);
            $result['duplicate'] = null !== $this->repository->findOneByNewsAndYoutubeId($news, $parsed['videoId']);

            try {
                $result['title'] = $this->youTubeClient->fetchOEmbed($parsed['videoId'])['title'];
            } catch (GalleryException $e) {
                $result['error'] = $e->getMessage();
            }

            $results[] = $result;

            if (\count($results) >= 20) {
                break;
            }
        }

        return $results;
    }

    public function addYouTubeVideo(News $news, string $url, ?string $caption = null): NewsGalleryItem
    {
        $videoId = YouTubeUrlParser::extractId($url);
        if (null === $videoId) {
            throw new GalleryException('Link do YouTube não reconhecido. Cole o endereço completo do vídeo.');
        }

        // Garante que o vídeo existe e é público/incorporável; o título vira a legenda sugerida.
        $oembed = $this->youTubeClient->fetchOEmbed($videoId);
        $caption = null !== $caption && '' !== trim($caption) ? $caption : $oembed['title'];

        $tmp = $this->youTubeClient->downloadThumbnail($videoId);

        try {
            $absoluteDir = $this->storage->prepareDirFor($news);
            $files = $this->imageProcessor->process(
                $tmp,
                $absoluteDir,
                $this->storage->relativeDirFor($news),
                $this->uniqueBaseName('youtube-'.$videoId),
                ['thumb', 'medium'],
                'image/jpeg',
                trimLetterbox: true,
            );
        } finally {
            @unlink($tmp);
        }

        $item = (new NewsGalleryItem(NewsGalleryItemType::YouTube))
            ->setYoutubeId($videoId)
            ->setCaption($caption)
            ->setFiles($files)
            ->setPosition($this->repository->getNextPosition($news));

        return $this->persistNewItem($news, $item);
    }

    public function updateCaption(NewsGalleryItem $item, ?string $caption): void
    {
        if (null !== $caption && mb_strlen(trim($caption)) > NewsGalleryItem::CAPTION_MAX_LENGTH) {
            throw new GalleryException(sprintf('A legenda pode ter no máximo %d caracteres.', NewsGalleryItem::CAPTION_MAX_LENGTH));
        }

        $item->setCaption($caption);
        $this->entityManager->flush();
    }

    public function toggleActive(NewsGalleryItem $item): void
    {
        $item->setActive(!$item->isActive());
        $this->entityManager->flush();
    }

    /**
     * Reordena conforme a lista de IDs recebida (na ordem desejada).
     * IDs de outras notícias são ignorados; itens não enviados vão para o final.
     *
     * @param list<int|string> $orderedIds
     */
    public function reorder(News $news, array $orderedIds): void
    {
        $items = [];
        foreach ($this->repository->findByNewsOrdered($news) as $item) {
            $items[$item->getId()] = $item;
        }

        $position = 0;
        foreach ($orderedIds as $id) {
            $id = (int) $id;
            if (isset($items[$id])) {
                $items[$id]->setPosition($position++);
                unset($items[$id]);
            }
        }

        foreach ($items as $item) {
            $item->setPosition($position++);
        }

        $this->entityManager->flush();
    }

    /**
     * Remove o item; os arquivos gerados são apagados após o flush (GalleryFileCleanupListener).
     */
    public function delete(NewsGalleryItem $item): void
    {
        $item->getNews()?->removeGalleryItem($item);
        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }

    private function validateUpload(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new GalleryException(match ($file->getError()) {
                \UPLOAD_ERR_INI_SIZE, \UPLOAD_ERR_FORM_SIZE => sprintf(
                    'O arquivo excede o limite de envio do servidor (%s por arquivo).',
                    self::formatBytes($this->getMaxUploadBytes())
                ),
                \UPLOAD_ERR_PARTIAL => 'O envio foi interrompido antes de terminar. Tente novamente.',
                \UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi recebido.',
                default => sprintf('Falha no envio do arquivo (código %d). Tente novamente.', $file->getError()),
            });
        }

        if ($file->getSize() > $this->getMaxUploadBytes()) {
            throw new GalleryException(sprintf(
                'A imagem tem %s; o limite é de %s por arquivo.',
                self::formatBytes((int) $file->getSize()),
                self::formatBytes($this->getMaxUploadBytes())
            ));
        }

        $mimeType = (string) $file->getMimeType();
        if (!isset(self::ALLOWED_MIME_TYPES[$mimeType])) {
            throw new GalleryException('Formato não aceito. Envie imagens JPG, PNG ou WebP.');
        }
    }

    private function persistNewItem(News $news, NewsGalleryItem $item): NewsGalleryItem
    {
        $news->addGalleryItem($item);
        $this->entityManager->persist($item);

        try {
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $this->storage->delete($item->getFilePaths());

            throw $e;
        }

        return $item;
    }

    /**
     * Nome de arquivo seguro e único: slug do nome original (até 40 caracteres) + sufixo aleatório.
     */
    private function uniqueBaseName(string $name): string
    {
        $slug = strtolower((string) $this->slugger->slug($name));
        $slug = trim(mb_substr($slug, 0, 40), '-');

        return ('' !== $slug ? $slug : 'imagem').'-'.bin2hex(random_bytes(5));
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return str_replace('.', ',', rtrim(rtrim(number_format($bytes / 1024 / 1024, 1, '.', ''), '0'), '.')).' MB';
        }

        return max(1, (int) round($bytes / 1024)).' KB';
    }
}

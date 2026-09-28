<?php

namespace App\Controller\Admin;

use App\Entity\News;
use App\Entity\NewsGalleryItem;
use App\Service\Gallery\GalleryException;
use App\Service\Gallery\GalleryStorage;
use App\Service\Gallery\NewsGalleryManager;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Ações AJAX da galeria de uma notícia (imagens e vídeos do YouTube).
 * Disponível para jornalistas e administradores.
 */
#[Route('/admin/news/{id}/gallery', requirements: ['id' => '\d+'])]
#[IsGranted('ROLE_JORNALISTA')]
class NewsGalleryController extends AbstractController
{
    use AdminAjaxTrait;

    public function __construct(
        private readonly NewsGalleryManager $manager,
        private readonly GalleryStorage $storage,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Upload de UMA imagem por requisição (o navegador envia várias em paralelo, com progresso individual).
     */
    #[Route('/upload', name: 'admin_news_gallery_upload', methods: ['POST'])]
    public function upload(Request $request, News $news): JsonResponse
    {
        if ($error = $this->checkAjaxCsrf($request)) {
            return $error;
        }

        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            // Quando o arquivo passa de post_max_size, o PHP descarta o corpo inteiro da requisição.
            return $this->jsonError(sprintf(
                'Nenhum arquivo foi recebido. Verifique se a imagem tem no máximo %s.',
                NewsGalleryManager::formatBytes($this->manager->getMaxUploadBytes())
            ));
        }

        try {
            $item = $this->manager->addImage($news, $file);
        } catch (GalleryException $e) {
            return $this->jsonError($e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error('Erro inesperado no upload da galeria: '.$e->getMessage(), ['exception' => $e]);

            return $this->jsonError('Erro inesperado ao processar a imagem. Tente novamente.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->itemResponse($item, 'Imagem adicionada à galeria.', Response::HTTP_CREATED);
    }

    /**
     * Pré-visualização dos links do YouTube colados (título pelo oEmbed + capa), sem gravar nada.
     */
    #[Route('/youtube/preview', name: 'admin_news_gallery_youtube_preview', methods: ['POST'])]
    public function youtubePreview(Request $request, News $news): JsonResponse
    {
        if ($error = $this->checkAjaxCsrf($request)) {
            return $error;
        }

        $urls = $this->jsonBody($request)['urls'] ?? [];
        if (\is_string($urls)) {
            $urls = [$urls];
        }

        $urls = array_values(array_filter(\is_array($urls) ? $urls : [], 'is_string'));
        if ([] === $urls) {
            return $this->jsonError('Cole ao menos um link do YouTube.');
        }

        return new JsonResponse(['results' => $this->manager->previewYouTube($news, $urls)]);
    }

    #[Route('/youtube', name: 'admin_news_gallery_youtube_add', methods: ['POST'])]
    public function youtubeAdd(Request $request, News $news): JsonResponse
    {
        if ($error = $this->checkAjaxCsrf($request)) {
            return $error;
        }

        $body = $this->jsonBody($request);
        $url = \is_string($body['url'] ?? null) ? $body['url'] : '';
        $caption = \is_string($body['caption'] ?? null) ? $body['caption'] : null;

        try {
            $item = $this->manager->addYouTubeVideo($news, $url, $caption);
        } catch (GalleryException $e) {
            return $this->jsonError($e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error('Erro inesperado ao adicionar vídeo do YouTube: '.$e->getMessage(), ['exception' => $e]);

            return $this->jsonError('Erro inesperado ao adicionar o vídeo. Tente novamente.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->itemResponse($item, 'Vídeo adicionado à galeria.', Response::HTTP_CREATED);
    }

    #[Route('/reorder', name: 'admin_news_gallery_reorder', methods: ['POST'])]
    public function reorder(Request $request, News $news): JsonResponse
    {
        if ($error = $this->checkAjaxCsrf($request)) {
            return $error;
        }

        $ids = $this->jsonBody($request)['ids'] ?? null;
        if (!\is_array($ids)) {
            return $this->jsonError('Ordem inválida.');
        }

        $this->manager->reorder($news, array_values($ids));

        return new JsonResponse(['message' => 'Nova ordem salva.']);
    }

    #[Route('/{itemId}/caption', name: 'admin_news_gallery_caption', requirements: ['itemId' => '\d+'], methods: ['POST'])]
    public function caption(
        Request $request,
        News $news,
        #[MapEntity(id: 'itemId')] NewsGalleryItem $item,
    ): JsonResponse {
        if ($error = $this->checkAjaxCsrf($request) ?? $this->checkOwnership($news, $item)) {
            return $error;
        }

        $caption = $this->jsonBody($request)['caption'] ?? null;

        try {
            $this->manager->updateCaption($item, \is_string($caption) ? $caption : null);
        } catch (GalleryException $e) {
            return $this->jsonError($e->getMessage());
        }

        return new JsonResponse(['caption' => $item->getCaption(), 'message' => 'Legenda salva.']);
    }

    #[Route('/{itemId}/toggle-active', name: 'admin_news_gallery_toggle_active', requirements: ['itemId' => '\d+'], methods: ['POST'])]
    public function toggleActive(
        Request $request,
        News $news,
        #[MapEntity(id: 'itemId')] NewsGalleryItem $item,
    ): JsonResponse {
        if ($error = $this->checkAjaxCsrf($request) ?? $this->checkOwnership($news, $item)) {
            return $error;
        }

        $this->manager->toggleActive($item);

        return new JsonResponse([
            'active' => $item->isActive(),
            'message' => $item->isActive()
                ? 'Item ativado: ele aparece na galeria do site.'
                : 'Item desativado: ele não aparece mais na galeria do site.',
        ]);
    }

    #[Route('/{itemId}/delete', name: 'admin_news_gallery_delete', requirements: ['itemId' => '\d+'], methods: ['POST'])]
    public function delete(
        Request $request,
        News $news,
        #[MapEntity(id: 'itemId')] NewsGalleryItem $item,
    ): JsonResponse {
        if ($error = $this->checkAjaxCsrf($request) ?? $this->checkOwnership($news, $item)) {
            return $error;
        }

        $this->manager->delete($item);

        return new JsonResponse(['message' => 'Item excluído da galeria.']);
    }

    private function checkOwnership(News $news, NewsGalleryItem $item): ?JsonResponse
    {
        if ($item->getNews()?->getId() !== $news->getId()) {
            return $this->jsonError('Item não encontrado nesta notícia.', Response::HTTP_NOT_FOUND);
        }

        return null;
    }

    private function itemResponse(NewsGalleryItem $item, string $message, int $status = Response::HTTP_OK): JsonResponse
    {
        $thumb = $item->getVariant('thumb');

        return new JsonResponse([
            'message' => $message,
            'item' => [
                'id' => $item->getId(),
                'type' => $item->getType()->value,
                'caption' => $item->getCaption(),
                'active' => $item->isActive(),
                'position' => $item->getPosition(),
                'youtubeId' => $item->getYoutubeId(),
                'thumbUrl' => $thumb ? $this->storage->publicUrl($thumb['path']) : null,
            ],
            'html' => $this->renderView('admin/news_gallery/_item.html.twig', [
                'news' => $item->getNews(),
                'item' => $item,
            ]),
        ], $status);
    }
}

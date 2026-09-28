<?php

namespace App\Tests\Controller\Admin;

use App\Entity\News;
use App\Entity\NewsGalleryItem;
use App\Entity\User;
use App\Repository\NewsGalleryItemRepository;
use App\Tests\Support\AppWebTestCase;
use App\Tests\Support\TestImageFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Galeria da notícia no painel: upload, legenda, reordenação, ativar/desativar, exclusão e YouTube.
 */
final class NewsGalleryControllerTest extends AppWebTestCase
{
    private News $news;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loginAs(User::ROLE_JORNALISTA);
        $this->news = $this->createNews('Notícia com galeria');
        $this->token = $this->ajaxToken($this->news);
    }

    public function testUploadGeneratesResizedVersionsAndFixesExifOrientation(): void
    {
        // 300x200 com orientação 6 (celular "deitado"): depois de corrigida vira 200x300.
        $response = $this->upload(TestImageFactory::jpeg(300, 200, exifOrientation: 6), 'Foto do Evento 2026.jpg');

        self::assertResponseStatusCodeSame(201);
        self::assertSame('image', $response['item']['type']);
        self::assertStringContainsString('<li', $response['html']);

        $item = $this->findItem($response['item']['id']);
        self::assertSame(['thumb', 'medium', 'large'], array_keys($item->getFiles()));
        self::assertSame(200, $item->getVariant('thumb')['width']);
        self::assertSame(300, $item->getVariant('thumb')['height']);
        self::assertSame('Foto do Evento 2026.jpg', $item->getOriginalFilename());

        foreach ($item->getFilePaths() as $path) {
            self::assertFileExists($this->galleryDir().'/'.$path);
            self::assertMatchesRegularExpression('#^\d+/foto-do-evento-2026-[a-f0-9]{10}-(thumb|medium|large)\.(webp|jpg)$#', $path, 'Nome de arquivo seguro e único.');
        }

        [$width, $height] = getimagesize($this->galleryDir().'/'.$item->getVariant('large')['path']);
        self::assertSame([200, 300], [$width, $height]);
    }

    public function testLargeImagesAreScaledDownPerVersion(): void
    {
        $response = $this->upload(TestImageFactory::jpeg(3000, 2000), 'grande.jpg');
        self::assertResponseStatusCodeSame(201);

        $item = $this->findItem($response['item']['id']);
        self::assertSame([400, 267], [$item->getVariant('thumb')['width'], $item->getVariant('thumb')['height']]);
        self::assertSame([1200, 800], [$item->getVariant('medium')['width'], $item->getVariant('medium')['height']]);
        self::assertSame([2400, 1600], [$item->getVariant('large')['width'], $item->getVariant('large')['height']]);
    }

    public function testPngIsAccepted(): void
    {
        $this->upload(TestImageFactory::png(500, 500), 'grafico.png', 'image/png');

        self::assertResponseStatusCodeSame(201);
    }

    public function testRejectsUnsupportedFileType(): void
    {
        $response = $this->upload(TestImageFactory::gif(50, 50), 'animacao.gif', 'image/gif');

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('Formato não aceito', $response['error']);
        self::assertCount(0, static::getContainer()->get(NewsGalleryItemRepository::class)->findAll());
    }

    public function testRejectsFakeImageWithImageExtension(): void
    {
        $response = $this->upload('isto não é uma imagem', 'falsa.jpg');

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('Formato não aceito', $response['error']);
    }

    public function testRejectsRequestWithoutCsrfToken(): void
    {
        $path = TestImageFactory::toTempFile(TestImageFactory::jpeg(100, 100), 'jpg');
        $this->client->request('POST', $this->url('/upload'), [], ['file' => new UploadedFile($path, 'foto.jpg', 'image/jpeg', null, true)], ['HTTP_ACCEPT' => 'application/json']);

        self::assertResponseStatusCodeSame(403);
    }

    public function testRequestWithoutFileReturnsClearMessage(): void
    {
        $this->client->request('POST', $this->url('/upload'), [], [], ['HTTP_X_CSRF_TOKEN' => $this->token]);

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('Nenhum arquivo foi recebido', (string) $this->client->getResponse()->getContent());
    }

    public function testCaptionIsSavedInline(): void
    {
        $id = $this->upload(TestImageFactory::jpeg(100, 100), 'a.jpg')['item']['id'];

        $response = $this->postJson($this->url('/'.$id.'/caption'), ['caption' => '  Abertura   do congresso  '], $this->token);

        self::assertResponseIsSuccessful();
        self::assertSame('Abertura do congresso', $response['caption']);
        self::assertSame('Abertura do congresso', $this->findItem($id)->getCaption());

        $this->postJson($this->url('/'.$id.'/caption'), ['caption' => ''], $this->token);
        self::assertNull($this->findItem($id)->getCaption());
    }

    public function testCaptionTooLongIsRejected(): void
    {
        $id = $this->upload(TestImageFactory::jpeg(100, 100), 'a.jpg')['item']['id'];

        $response = $this->postJson($this->url('/'.$id.'/caption'), ['caption' => str_repeat('a', 501)], $this->token);

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('500 caracteres', $response['error']);
    }

    public function testReorderSavesPositions(): void
    {
        $first = $this->upload(TestImageFactory::jpeg(100, 100), '1.jpg')['item']['id'];
        $second = $this->upload(TestImageFactory::jpeg(100, 100), '2.jpg')['item']['id'];
        $third = $this->upload(TestImageFactory::jpeg(100, 100), '3.jpg')['item']['id'];

        $this->postJson($this->url('/reorder'), ['ids' => [$third, $first, $second]], $this->token);
        self::assertResponseIsSuccessful();

        $this->em()->clear();
        $ordered = static::getContainer()->get(NewsGalleryItemRepository::class)->findByNewsOrdered($this->em()->find(News::class, $this->news->getId()));
        self::assertSame([$third, $first, $second], array_map(static fn (NewsGalleryItem $item) => $item->getId(), $ordered));
    }

    public function testToggleActiveKeepsItemInPanel(): void
    {
        $id = $this->upload(TestImageFactory::jpeg(100, 100), 'a.jpg')['item']['id'];

        $response = $this->postJson($this->url('/'.$id.'/toggle-active'), [], $this->token);
        self::assertResponseIsSuccessful();
        self::assertFalse($response['active']);
        self::assertFalse($this->findItem($id)->isActive());

        $crawler = $this->client->request('GET', '/admin/news/'.$this->news->getId().'/edit');
        $card = $crawler->filter(sprintf('[data-news-gallery-admin-target="item"][data-id="%d"]', $id));
        self::assertCount(1, $card, 'Item inativo continua visível no painel.');
        self::assertStringContainsString('is-inactive', (string) $card->attr('class'));
        self::assertStringContainsString('Inativo', $card->text());
    }

    public function testDeleteRemovesItemAndGeneratedFiles(): void
    {
        $id = $this->upload(TestImageFactory::jpeg(100, 100), 'a.jpg')['item']['id'];
        $paths = $this->findItem($id)->getFilePaths();

        $this->postJson($this->url('/'.$id.'/delete'), [], $this->token);

        self::assertResponseIsSuccessful();
        $this->em()->clear();
        self::assertNull($this->em()->find(NewsGalleryItem::class, $id));
        foreach ($paths as $path) {
            self::assertFileDoesNotExist($this->galleryDir().'/'.$path);
        }
    }

    public function testDeletingNewsRemovesGalleryFiles(): void
    {
        $id = $this->upload(TestImageFactory::jpeg(100, 100), 'a.jpg')['item']['id'];
        $paths = $this->findItem($id)->getFilePaths();

        $crawler = $this->client->request('GET', '/admin/news/');
        $deleteForm = $crawler->filter(sprintf('form[action="/admin/news/%d"]', $this->news->getId()))->form();
        $this->client->submit($deleteForm);

        self::assertResponseRedirects('/admin/news/');
        foreach ($paths as $path) {
            self::assertFileDoesNotExist($this->galleryDir().'/'.$path);
        }
    }

    public function testItemFromAnotherNewsIsNotFound(): void
    {
        $id = $this->upload(TestImageFactory::jpeg(100, 100), 'a.jpg')['item']['id'];
        $otherNews = $this->createNews('Outra notícia');

        $this->postJson('/admin/news/'.$otherNews->getId().'/gallery/'.$id.'/delete', [], $this->token);

        self::assertResponseStatusCodeSame(404);
        self::assertNotNull($this->findItem($id));
    }

    public function testYoutubePreviewReturnsTitleAndErrors(): void
    {
        $response = $this->postJson($this->url('/youtube/preview'), ['urls' => [
            "https://youtu.be/dQw4w9WgXcQ?t=5\nhttps://www.youtube.com/shorts/PRIVATEvid1",
            'https://m.youtube.com/watch?v=MISSINGvid1&list=abc',
            'https://exemplo.com/video',
        ]], $this->token);

        self::assertResponseIsSuccessful();
        $results = $response['results'];
        self::assertCount(4, $results);

        self::assertSame('dQw4w9WgXcQ', $results[0]['videoId']);
        self::assertSame('Vídeo de teste dQw4w9WgXcQ', $results[0]['title']);
        self::assertNull($results[0]['error']);
        self::assertStringContainsString('i.ytimg.com/vi/dQw4w9WgXcQ/', $results[0]['thumbnailUrl']);

        self::assertStringContainsString('privado', $results[1]['error']);
        self::assertStringContainsString('não encontrado', $results[2]['error']);
        self::assertNull($results[3]['videoId']);
        self::assertStringContainsString('não reconhecido', $results[3]['error']);
    }

    public function testAddYoutubeVideoDownloadsCoverAndUsesTitleAsCaption(): void
    {
        $response = $this->postJson($this->url('/youtube'), ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10s', 'caption' => ''], $this->token);

        self::assertResponseStatusCodeSame(201);
        $item = $this->findItem($response['item']['id']);
        self::assertTrue($item->isVideo());
        self::assertSame('dQw4w9WgXcQ', $item->getYoutubeId());
        self::assertSame('Vídeo de teste dQw4w9WgXcQ', $item->getCaption());
        self::assertSame(['thumb', 'medium'], array_keys($item->getFiles()));
        self::assertSame([1200, 675], [$item->getVariant('medium')['width'], $item->getVariant('medium')['height']], 'Capa maxres (1280x720) reduzida para a versão média.');
        self::assertFileExists($this->galleryDir().'/'.$item->getVariant('thumb')['path']);
        self::assertStringContainsString('data-drag-handle', $response['html']);
    }

    public function testCustomCaptionIsKeptForYoutubeVideo(): void
    {
        $response = $this->postJson($this->url('/youtube'), ['url' => 'https://youtu.be/dQw4w9WgXcQ', 'caption' => 'Entrevista com o cardiologista'], $this->token);

        self::assertSame('Entrevista com o cardiologista', $this->findItem($response['item']['id'])->getCaption());
    }

    public function testYoutubeCoverFallsBackWhenMaxresIsMissingOrGray(): void
    {
        foreach (['NOMAXRESvid', 'GRAYMAXRES1'] as $videoId) {
            $response = $this->postJson($this->url('/youtube'), ['url' => 'https://youtu.be/'.$videoId], $this->token);

            self::assertResponseStatusCodeSame(201, $videoId);
            $medium = $this->findItem($response['item']['id'])->getVariant('medium');
            self::assertSame([640, 480], [$medium['width'], $medium['height']], $videoId.' deve usar a capa sddefault.');
        }
    }

    public function testLetterboxedCoverIsCroppedTo16By9(): void
    {
        $response = $this->postJson($this->url('/youtube'), ['url' => 'https://youtu.be/LETTERBOXv1'], $this->token);

        $medium = $this->findItem($response['item']['id'])->getVariant('medium');
        self::assertSame([640, 360], [$medium['width'], $medium['height']]);
    }

    public function testPrivateOrMissingVideoIsRejectedWithClearMessage(): void
    {
        $private = $this->postJson($this->url('/youtube'), ['url' => 'https://youtu.be/PRIVATEvid1'], $this->token);
        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('privado', $private['error']);

        $missing = $this->postJson($this->url('/youtube'), ['url' => 'https://youtu.be/MISSINGvid1'], $this->token);
        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('não encontrado', $missing['error']);

        $invalid = $this->postJson($this->url('/youtube'), ['url' => 'https://vimeo.com/123'], $this->token);
        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('não reconhecido', $invalid['error']);

        self::assertCount(0, static::getContainer()->get(NewsGalleryItemRepository::class)->findAll());
    }

    public function testAdminCanAlsoManageGallery(): void
    {
        $this->client->loginUser($this->createUser(User::ROLE_ADMIN));
        $token = $this->ajaxToken($this->news);

        $this->client->request('POST', $this->url('/upload'), [], [
            'file' => new UploadedFile(TestImageFactory::toTempFile(TestImageFactory::jpeg(120, 80), 'jpg'), 'admin.jpg', 'image/jpeg', null, true),
        ], ['HTTP_X_CSRF_TOKEN' => $token, 'HTTP_ACCEPT' => 'application/json']);

        self::assertResponseStatusCodeSame(201);
    }

    private function url(string $suffix): string
    {
        return '/admin/news/'.$this->news->getId().'/gallery'.$suffix;
    }

    /**
     * @return array<string, mixed>
     */
    private function upload(string $content, string $originalName, string $mimeType = 'image/jpeg'): array
    {
        $extension = pathinfo($originalName, \PATHINFO_EXTENSION) ?: 'bin';
        $path = TestImageFactory::toTempFile($content, $extension);

        $this->client->request('POST', $this->url('/upload'), [], [
            'file' => new UploadedFile($path, $originalName, $mimeType, null, true),
        ], ['HTTP_X_CSRF_TOKEN' => $this->token, 'HTTP_ACCEPT' => 'application/json']);

        return json_decode((string) $this->client->getResponse()->getContent(), true) ?? [];
    }

    private function findItem(int $id): ?NewsGalleryItem
    {
        $this->em()->clear();

        return $this->em()->find(NewsGalleryItem::class, $id);
    }
}

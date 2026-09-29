<?php

namespace App\Tests\Support;

use App\Entity\GeneralData;
use App\Entity\News;
use App\Entity\NewsCategory;
use App\Entity\NewsGalleryItem;
use App\Entity\NewsGalleryItemType;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Base dos testes funcionais.
 *
 * O ambiente de teste usa SQLite (var/test.db, ver .env.test): o schema é recriado a partir do
 * mapeamento Doctrine no início de cada teste, então não é preciso MySQL para rodar a suíte.
 * Os arquivos da galeria vão para var/test/news-gallery (parâmetro app.news_gallery.dir em when@test).
 */
abstract class AppWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->client->disableReboot();

        $em = $this->em();
        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        // Como em produção, existe um registro de "Dados Gerais" (usado no cabeçalho/rodapé públicos).
        $em->persist(new GeneralData());
        $em->flush();

        (new Filesystem())->remove($this->galleryDir());
        YouTubeMockResponseFactory::$requests = [];
    }

    protected function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function galleryDir(): string
    {
        return (string) static::getContainer()->getParameter('app.news_gallery.dir');
    }

    protected function createUser(string $accessType, ?string $email = null): User
    {
        $user = (new User())
            ->setEmail($email ?? strtolower(str_replace('ROLE_', '', $accessType)).'-'.bin2hex(random_bytes(3)).'@teste.local')
            ->setAccessType($accessType)
            ->setPassword('not-used-in-tests');

        $this->em()->persist($user);
        $this->em()->flush();

        return $user;
    }

    protected function loginAs(string $accessType): User
    {
        $user = $this->createUser($accessType);
        $this->client->loginUser($user);

        return $user;
    }

    protected function createNews(string $title, bool $active = true, ?\DateTimeImmutable $publishedAt = null, array $categories = []): News
    {
        $news = (new News())
            ->setTitle($title)
            ->setSlug(strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-')))
            ->setSummary('Resumo de '.$title)
            ->setContent('<p>Conteúdo de '.$title.'</p>')
            ->setPublishedAt($publishedAt ?? new \DateTimeImmutable('-1 day'))
            ->setActive($active);

        foreach ($categories as $category) {
            $news->addCategory($category);
        }

        $this->em()->persist($news);
        $this->em()->flush();

        return $news;
    }

    protected function createCategory(string $title, bool $active = true): NewsCategory
    {
        $category = (new NewsCategory())
            ->setTitle($title)
            ->setSlug(strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-')))
            ->setActive($active);

        $this->em()->persist($category);
        $this->em()->flush();

        return $category;
    }

    /**
     * Item de galeria "fake" (sem processar imagem), para testes de exibição pública.
     */
    protected function createGalleryItem(News $news, string $caption, bool $active = true, int $position = 0, ?string $youtubeId = null): NewsGalleryItem
    {
        $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $caption), '-'));
        $files = [
            'thumb' => ['path' => $news->getId().'/'.$slug.'-thumb.webp', 'width' => 400, 'height' => 300],
            'medium' => ['path' => $news->getId().'/'.$slug.'-medium.webp', 'width' => 1200, 'height' => 900],
        ];

        if (null === $youtubeId) {
            $files['large'] = ['path' => $news->getId().'/'.$slug.'-large.webp', 'width' => 2400, 'height' => 1800];
        }

        $item = (new NewsGalleryItem(null === $youtubeId ? NewsGalleryItemType::Image : NewsGalleryItemType::YouTube))
            ->setCaption($caption)
            ->setActive($active)
            ->setPosition($position)
            ->setYoutubeId($youtubeId)
            ->setFiles($files);
        $news->addGalleryItem($item);

        $this->em()->persist($item);
        $this->em()->flush();

        return $item;
    }

    /**
     * Token CSRF das ações AJAX do painel, lido da própria página de edição da notícia.
     */
    protected function ajaxToken(News $news): string
    {
        $crawler = $this->client->request('GET', '/admin/news/'.$news->getId().'/edit');
        self::assertResponseIsSuccessful();

        return (string) $crawler->filter('[data-news-gallery-admin-token-value]')->attr('data-news-gallery-admin-token-value');
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function postJson(string $url, array $payload, string $token): array
    {
        $this->client->request('POST', $url, [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_CSRF_TOKEN' => $token,
        ], json_encode($payload, \JSON_THROW_ON_ERROR));

        return json_decode((string) $this->client->getResponse()->getContent(), true) ?? [];
    }
}

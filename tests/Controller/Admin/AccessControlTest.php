<?php

namespace App\Tests\Controller\Admin;

use App\Entity\User;
use App\Tests\Support\AppWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Controle de acesso do perfil Jornalista (ROLE_JORNALISTA) versus Administrador (ROLE_ADMIN).
 */
final class AccessControlTest extends AppWebTestCase
{
    /**
     * Áreas que continuam exclusivas do administrador.
     *
     * @return iterable<string, array{0: string}>
     */
    public static function adminOnlyUrls(): iterable
    {
        yield 'banners' => ['/admin/banners/'];
        yield 'parceiros' => ['/admin/partner/'];
        yield 'seo' => ['/admin/page-seo/edit'];
        yield 'páginas institucionais' => ['/admin/paginas/'];
        yield 'quem somos' => ['/admin/about/'];
        yield 'linha do tempo' => ['/admin/timeline/'];
        yield 'serviços' => ['/admin/service/'];
        yield 'equipe' => ['/admin/doctor/'];
        yield 'transparência' => ['/admin/transparency-category/'];
        yield 'documentos' => ['/admin/transparency/'];
        yield 'especialidades' => ['/admin/specialty/'];
        yield 'depoimentos' => ['/admin/testimony/'];
        yield 'newsletter' => ['/admin/newsletter/'];
        yield 'mensagens' => ['/admin/messages/'];
        yield 'variáveis' => ['/admin/system-variables/'];
        yield 'dados gerais' => ['/admin/general-data/edit'];
        yield 'usuários' => ['/admin/usuarios/'];
        yield 'novo usuário' => ['/admin/usuarios/novo'];
    }

    /**
     * Áreas liberadas para o jornalista.
     *
     * @return iterable<string, array{0: string}>
     */
    public static function journalistUrls(): iterable
    {
        yield 'notícias' => ['/admin/news/'];
        yield 'nova notícia' => ['/admin/news/new'];
        yield 'categorias' => ['/admin/news-category/'];
        yield 'nova categoria' => ['/admin/news-category/new'];
    }

    public function testAnonymousIsRedirectedToLogin(): void
    {
        $this->client->request('GET', '/admin/news/');

        self::assertResponseRedirects('/login');
    }

    #[DataProvider('journalistUrls')]
    public function testJournalistCanAccessNewsAreas(string $url): void
    {
        $this->loginAs(User::ROLE_JORNALISTA);
        $this->client->request('GET', $url);

        self::assertResponseIsSuccessful();
    }

    #[DataProvider('adminOnlyUrls')]
    public function testJournalistGetsForbiddenOnOtherAreas(string $url): void
    {
        $this->loginAs(User::ROLE_JORNALISTA);
        $this->client->request('GET', $url);

        self::assertResponseStatusCodeSame(403);
    }

    public function testJournalistCannotSubmitAdminOnlyForms(): void
    {
        $this->loginAs(User::ROLE_JORNALISTA);
        $this->client->request('POST', '/admin/usuarios/novo', ['user' => ['email' => 'invasor@teste.local']]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testJournalistCanEditNewsAndManageGallery(): void
    {
        $this->loginAs(User::ROLE_JORNALISTA);
        $news = $this->createNews('Notícia do jornalista');

        $crawler = $this->client->request('GET', '/admin/news/'.$news->getId().'/edit');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('#galeria[data-controller="news-gallery-admin"]'));
    }

    public function testDashboardRedirectsJournalistToNews(): void
    {
        $this->loginAs(User::ROLE_JORNALISTA);
        $this->client->request('GET', '/admin/');

        self::assertResponseRedirects('/admin/news/');
    }

    public function testJournalistMenuShowsOnlyNewsAreas(): void
    {
        $this->loginAs(User::ROLE_JORNALISTA);
        $crawler = $this->client->request('GET', '/admin/news/');

        $menu = $crawler->filter('aside nav');
        self::assertCount(1, $menu->filter('a[href="/admin/news/"]'));
        self::assertCount(1, $menu->filter('a[href="/admin/news-category/"]'));

        foreach (['/admin/', '/admin/service/', '/admin/doctor/', '/admin/usuarios/', '/admin/general-data/edit', '/admin/banners/'] as $forbiddenLink) {
            self::assertCount(0, $menu->filter(sprintf('a[href="%s"]', $forbiddenLink)), sprintf('O menu do jornalista não deveria ter o link %s', $forbiddenLink));
        }

        self::assertSelectorTextContains('header', 'Jornalista');
    }

    #[DataProvider('adminOnlyUrls')]
    public function testAdminKeepsFullAccess(string $url): void
    {
        $this->loginAs(User::ROLE_ADMIN);
        $this->client->request('GET', $url);

        self::assertResponseIsSuccessful();
    }

    public function testAdminAlsoAccessesNewsAreasAndDashboard(): void
    {
        $this->loginAs(User::ROLE_ADMIN);

        foreach (['/admin/', '/admin/news/', '/admin/news-category/'] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseIsSuccessful($url);
        }

        $crawler = $this->client->request('GET', '/admin/news/');
        self::assertCount(1, $crawler->filter('aside nav a[href="/admin/usuarios/"]'));
        self::assertCount(1, $crawler->filter('aside nav a[href="/admin/service/"]'));
    }
}

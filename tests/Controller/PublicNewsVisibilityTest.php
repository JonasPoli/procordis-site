<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\Support\AppWebTestCase;

/**
 * Campo "Ativo": notícias, categorias e itens de galeria inativos não aparecem no site público.
 */
final class PublicNewsVisibilityTest extends AppWebTestCase
{
    public function testInactiveNewsReturns404(): void
    {
        $news = $this->createNews('Notícia desativada', active: false);

        $this->client->request('GET', '/noticias/'.$news->getSlug());

        self::assertResponseStatusCodeSame(404);
    }

    public function testScheduledNewsReturns404(): void
    {
        $news = $this->createNews('Notícia agendada', publishedAt: new \DateTimeImmutable('+3 days'));

        $this->client->request('GET', '/noticias/'.$news->getSlug());

        self::assertResponseStatusCodeSame(404);
    }

    public function testActiveNewsIsPublished(): void
    {
        $news = $this->createNews('Notícia publicada');

        $this->client->request('GET', '/noticias/'.$news->getSlug());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Notícia publicada');
    }

    public function testInactiveNewsIsHiddenFromListingsHomeSearchAndSidebar(): void
    {
        $category = $this->createCategory('Eventos');
        $this->createNews('Visivel Alfa', categories: [$category]);
        $this->createNews('Escondida Beta', active: false, categories: [$category]);

        $this->client->request('GET', '/noticias/');
        self::assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Visivel Alfa', $html);
        self::assertStringNotContainsString('Escondida Beta', $html);

        $this->client->request('GET', '/noticias/?category=eventos');
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('Escondida Beta', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Visivel Alfa', $html);
        self::assertStringNotContainsString('Escondida Beta', $html);

        $this->client->request('GET', '/pesquisa?q=a');
        self::assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Visivel Alfa', $html);
        self::assertStringNotContainsString('Escondida Beta', $html);

        // "Posts recentes" e contagem da categoria na página de outra notícia.
        $crawler = $this->client->request('GET', '/noticias/visivel-alfa');
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('Escondida Beta', (string) $this->client->getResponse()->getContent());
        self::assertSame('1', trim($crawler->filter('aside a[href="/noticias/?category=eventos"] span')->last()->text()));
    }

    public function testJournalistCanPreviewInactiveNews(): void
    {
        $news = $this->createNews('Rascunho do jornalista', active: false);
        $this->loginAs(User::ROLE_JORNALISTA);

        $this->client->request('GET', '/noticias/'.$news->getSlug());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[role="status"]', 'Pré-visualização');
    }

    public function testInactiveCategoryPageReturns404ButItsNewsStayVisible(): void
    {
        $inactive = $this->createCategory('Categoria Oculta', active: false);
        $active = $this->createCategory('Categoria Aberta');
        $news = $this->createNews('Noticia Com Duas Categorias', categories: [$inactive, $active]);

        $this->client->request('GET', '/noticias/?category='.$inactive->getSlug());
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/noticias/?category='.$active->getSlug());
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Noticia Com Duas Categorias', (string) $this->client->getResponse()->getContent());

        // A notícia continua visível, mas sem link/etiqueta para a categoria inativa.
        foreach (['/noticias/', '/noticias/'.$news->getSlug()] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseIsSuccessful();
            $html = (string) $this->client->getResponse()->getContent();
            self::assertStringContainsString('Noticia Com Duas Categorias', $html);
            self::assertStringNotContainsString('category='.$inactive->getSlug(), $html, $url);
            self::assertStringNotContainsString('Categoria Oculta', $html, $url);
            self::assertStringContainsString('category='.$active->getSlug(), $html, $url);
        }
    }

    public function testInactiveGalleryItemIsNotShownPublicly(): void
    {
        $news = $this->createNews('Notícia com fotos');
        $this->createGalleryItem($news, 'Foto ativa da abertura', position: 0);
        $this->createGalleryItem($news, 'Foto inativa escondida', active: false, position: 1);
        $this->createGalleryItem($news, 'Video ativo', position: 2, youtubeId: 'dQw4w9WgXcQ');

        $crawler = $this->client->request('GET', '/noticias/'.$news->getSlug());

        self::assertResponseIsSuccessful();
        $gallery = $crawler->filter('.news-gallery');
        self::assertCount(1, $gallery);
        self::assertCount(2, $gallery->filter('.news-gallery__main .swiper-slide'));
        self::assertStringContainsString('Foto ativa da abertura', $gallery->html());
        self::assertStringNotContainsString('Foto inativa escondida', $gallery->html());
        self::assertStringNotContainsString('foto-inativa-escondida', (string) $this->client->getResponse()->getContent());

        // Ordem do painel, versão média no slideshow e grande no lightbox.
        $links = $gallery->filter('a.news-gallery__media');
        self::assertStringEndsWith('foto-ativa-da-abertura-large.webp', (string) $links->eq(0)->attr('href'));
        self::assertStringContainsString('foto-ativa-da-abertura-medium.webp', (string) $links->eq(0)->filter('img')->attr('src'));
        self::assertSame('Foto ativa da abertura', $links->eq(0)->filter('img')->attr('alt'));
        self::assertSame('youtube', $links->eq(1)->attr('data-pswp-type'));
        self::assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $links->eq(1)->attr('data-embed-url'));
    }

    public function testNewsWithoutGalleryShowsNothing(): void
    {
        $news = $this->createNews('Notícia sem galeria');
        $this->createGalleryItem($news, 'Somente inativa', active: false);

        $crawler = $this->client->request('GET', '/noticias/'.$news->getSlug());

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('.news-gallery'));
        // Nem os estilos/bibliotecas da galeria são carregados.
        self::assertCount(0, $crawler->filter('link[rel="stylesheet"][href*="news_gallery"]'));
        self::assertCount(0, $crawler->filter('link[rel="modulepreload"][href*="swiper"]'));
    }

    public function testGalleryAssetsAreLoadedOnlyWhenThereIsAGallery(): void
    {
        $news = $this->createNews('Notícia com assets');
        $this->createGalleryItem($news, 'Uma foto');

        $crawler = $this->client->request('GET', '/noticias/'.$news->getSlug());

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('link[rel="stylesheet"][href*="news_gallery"]'));
        self::assertGreaterThan(0, $crawler->filter('link[rel="modulepreload"][href*="swiper"]')->count());
    }
}

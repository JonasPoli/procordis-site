<?php

namespace App\Tests\Controller\Admin;

use App\Entity\News;
use App\Entity\NewsCategory;
use App\Entity\User;
use App\Tests\Support\AppWebTestCase;

/**
 * Alternância rápida de "Ativo" nas listagens e filtro por status.
 */
final class ActiveStatusTest extends AppWebTestCase
{
    public function testNewsDefaultsToActive(): void
    {
        self::assertTrue((new News())->isActive());
        self::assertTrue((new NewsCategory())->isActive());
    }

    public function testQuickToggleOnNewsListing(): void
    {
        $this->loginAs(User::ROLE_JORNALISTA);
        $news = $this->createNews('Alternar status');
        $token = $this->ajaxToken($news);

        $response = $this->postJson('/admin/news/'.$news->getId().'/toggle-active', [], $token);
        self::assertResponseIsSuccessful();
        self::assertFalse($response['active']);
        self::assertStringContainsString('desativada', $response['message']);

        $this->em()->clear();
        self::assertFalse($this->em()->find(News::class, $news->getId())->isActive());

        $this->client->request('GET', '/noticias/'.$news->getSlug());
        self::assertResponseIsSuccessful('Jornalista logado vê a pré-visualização.');

        $response = $this->postJson('/admin/news/'.$news->getId().'/toggle-active', [], $token);
        self::assertTrue($response['active']);
    }

    public function testToggleRequiresCsrfToken(): void
    {
        $this->loginAs(User::ROLE_JORNALISTA);
        $news = $this->createNews('Sem token');

        $this->postJson('/admin/news/'.$news->getId().'/toggle-active', [], 'token-invalido');

        self::assertResponseStatusCodeSame(403);
        $this->em()->clear();
        self::assertTrue($this->em()->find(News::class, $news->getId())->isActive());
    }

    public function testQuickToggleOnCategoryListing(): void
    {
        $this->loginAs(User::ROLE_JORNALISTA);
        $category = $this->createCategory('Campanhas');
        $token = $this->ajaxToken($this->createNews('Qualquer'));

        $response = $this->postJson('/admin/news-category/'.$category->getId().'/toggle-active', [], $token);

        self::assertResponseIsSuccessful();
        self::assertFalse($response['active']);
        $this->em()->clear();
        self::assertFalse($this->em()->find(NewsCategory::class, $category->getId())->isActive());
    }

    public function testStatusFilterOnNewsListing(): void
    {
        $this->loginAs(User::ROLE_JORNALISTA);
        $this->createNews('Filtro ativa');
        $this->createNews('Filtro inativa', active: false);

        $this->client->request('GET', '/admin/news/?status=ativos');
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Filtro ativa', $html);
        self::assertStringNotContainsString('Filtro inativa', $html);

        $this->client->request('GET', '/admin/news/?status=inativos');
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString('Filtro ativa', $html);
        self::assertStringContainsString('Filtro inativa', $html);

        $crawler = $this->client->request('GET', '/admin/news/');
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Filtro ativa', $html);
        self::assertStringContainsString('Filtro inativa', $html);
        self::assertCount(2, $crawler->filter('[data-controller="active-toggle"]'));
    }

    public function testStatusFilterOnCategoryListing(): void
    {
        $this->loginAs(User::ROLE_JORNALISTA);
        $this->createCategory('Cat ligada');
        $this->createCategory('Cat desligada', active: false);

        $this->client->request('GET', '/admin/news-category/?status=inativos');
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString('Cat ligada', $html);
        self::assertStringContainsString('Cat desligada', $html);
    }

    public function testNewsFormHasActiveSwitch(): void
    {
        $this->loginAs(User::ROLE_JORNALISTA);
        $crawler = $this->client->request('GET', '/admin/news/new');

        self::assertCount(1, $crawler->filter('input[name="news[active]"][type="checkbox"][checked]'), 'Nova notícia começa ativa.');
    }
}

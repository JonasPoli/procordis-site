<?php

namespace App\Controller;

use App\Repository\GeneralDataRepository;
use App\Repository\NewsCategoryRepository;
use App\Repository\NewsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/noticias')]
class NewsController extends AbstractController
{
    #[Route('/', name: 'app_news')]
    public function index(
        Request $request,
        NewsRepository $newsRepository,
        NewsCategoryRepository $newsCategoryRepository,
        GeneralDataRepository $generalDataRepository
    ): Response {
        $categorySlug = $request->query->get('category');
        $searchQuery = $request->query->get('q');

        $currentCategory = null;
        if ($categorySlug) {
            // Categoria inativa (ou inexistente) não tem página pública.
            $currentCategory = $newsCategoryRepository->findOnePublicBySlug((string) $categorySlug);

            if (!$currentCategory) {
                throw $this->createNotFoundException('Categoria não encontrada');
            }
        }

        $allNews = $newsRepository->findActive($currentCategory, $searchQuery ? (string) $searchQuery : null);

        return $this->render('news/index.html.twig', [
            'allNews' => $allNews,
            'currentCategory' => $currentCategory,
            'searchQuery' => $searchQuery,
            'generalData' => $generalDataRepository->findOneBy([]),
        ]);
    }

    #[Route('/{slug}', name: 'app_news_detail')]
    public function show(
        string $slug,
        NewsRepository $newsRepository,
        NewsCategoryRepository $newsCategoryRepository,
        GeneralDataRepository $generalDataRepository
    ): Response {
        $news = $newsRepository->findOnePublicBySlug($slug);
        $isPreview = false;

        // Notícia inativa ou agendada: 404 para o público. Quem gerencia notícias
        // (jornalista/administrador logado) pode pré-visualizá-la.
        if (!$news && $this->isGranted('ROLE_JORNALISTA')) {
            $news = $newsRepository->findOneBy(['slug' => $slug]);
            $isPreview = null !== $news;
        }

        if (!$news) {
            throw $this->createNotFoundException('Notícia não encontrada');
        }

        $recentNews = $newsRepository->findRecent(5, $news->getId());
        $previousNews = $newsRepository->findPrevious($news);
        $sidebarCategories = $newsCategoryRepository->findSidebarCategories();

        return $this->render('news/show.html.twig', [
            'news' => $news,
            'isPreview' => $isPreview,
            'recentNews' => $recentNews,
            'previousNews' => $previousNews,
            'sidebarCategories' => $sidebarCategories,
            'generalData' => $generalDataRepository->findOneBy([]),
        ]);
    }
}

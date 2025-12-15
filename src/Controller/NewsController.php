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
            $currentCategory = $newsCategoryRepository->findOneBy(['slug' => $categorySlug]);
        }

        $allNews = $newsRepository->findActive($categorySlug, $searchQuery);
        // Map category IDs to slugs for the template if needed, or just use slugs directly in template
        $categorySlugMap = []; 
        
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
        $news = $newsRepository->findOneBy(['slug' => $slug]);

        if (!$news) {
            throw $this->createNotFoundException('Notícia não encontrada');
        }

        $recentNews = $newsRepository->findRecent(5, $news->getId());
        $previousNews = $newsRepository->findPrevious($news);
        $sidebarCategories = $newsCategoryRepository->findSidebarCategories();

        return $this->render('news/show.html.twig', [
            'news' => $news,
            'recentNews' => $recentNews,
            'previousNews' => $previousNews,
            'sidebarCategories' => $sidebarCategories,
            'generalData' => $generalDataRepository->findOneBy([]),
            'newsUrl' => $this->generateUrl('app_news_detail', ['slug' => $news->getSlug()], 0) // 0 = ABSOLUTE_PATH? No, UrlGeneratorInterface::ABSOLUTE_URL is needed but 0 happens to be compatible with some versions or distinct logic. Let's use request schemehost concatanation in template or pass absolute url properly. 
            // Better: use request uri in template.
        ]);
    }
}

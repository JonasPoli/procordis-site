<?php

namespace App\Controller\Admin;

use App\Entity\News;
use App\Form\NewsType;
use App\Repository\NewsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/news')]
#[IsGranted('ROLE_ADMIN')]
class NewsController extends AbstractController
{
    #[Route('/', name: 'admin_news_index', methods: ['GET'])]
    public function index(NewsRepository $newsRepository): Response
    {
        return $this->render('admin/news/index.html.twig', [
            'news' => $newsRepository->findBy([], ['publishedAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'admin_news_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $news = new News();
        $form = $this->createForm(NewsType::class, $news);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (empty($news->getSlug())) {
                $news->setSlug($this->generateSlug($news->getTitle(), $slugger));
            }
            $entityManager->persist($news);
            $entityManager->flush();

            $this->addFlash('success', 'Notícia criada com sucesso!');

            return $this->redirectToRoute('admin_news_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/news/new.html.twig', [
            'news' => $news,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_news_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, News $news, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(NewsType::class, $news);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (empty($news->getSlug())) {
                $news->setSlug($this->generateSlug($news->getTitle(), $slugger));
            }
            $entityManager->flush();

            $this->addFlash('success', 'Notícia atualizada com sucesso!');

            return $this->redirectToRoute('admin_news_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/news/edit.html.twig', [
            'news' => $news,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_news_delete', methods: ['POST'])]
    public function delete(Request $request, News $news, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $news->getId(), $request->request->get('_token'))) {
            $entityManager->remove($news);
            $entityManager->flush();
            $this->addFlash('success', 'Notícia excluída com sucesso!');
        }

        return $this->redirectToRoute('admin_news_index', [], Response::HTTP_SEE_OTHER);
    }

    private function generateSlug(string $title, SluggerInterface $slugger): string
    {
        $stopWords = [
            'o',
            'a',
            'os',
            'as',
            'um',
            'uns',
            'uma',
            'umas',
            'de',
            'do',
            'da',
            'dos',
            'das',
            'em',
            'no',
            'na',
            'nos',
            'nas',
            'por',
            'pelo',
            'pela',
            'pelos',
            'pelas',
            'para',
            'pra',
            'pro',
            'com',
            'sem',
            'sob',
            'sobre',
            'entre',
            'ante',
            'ate',
            'contra',
            'desde',
            'e',
            'ou',
            'mas',
            'nem',
            'que',
            'se',
            'como',
            'pois',
            'porque',
            'eu',
            'voce',
            'ele',
            'ela',
            'nos',
            'eles',
            'elas',
            'meu',
            'minha',
            'teu',
            'tua',
            'seu',
            'sua',
            'nosso',
            'nossa',
            'esse',
            'essa',
            'isso',
            'este',
            'esta',
            'isto',
            'aquele',
            'aquela',
            'aquilo',
            'qual',
            'quais',
            'quem',
            'onde',
            'ser',
            'e',
            'eh',
            'foi',
            'fom',
            'sao',
            'era',
            'eram',
            'estar',
            'esta',
            'estao',
            'estava',
            'estavam',
            'ter',
            'tem',
            'tinha',
            'tinham',
            'teve',
            'muito',
            'mais',
            'menos',
            'ja',
            'agora',
            'tambe',
            'so',
            'talvez'
        ];

        // Convert to lowercase and create slug
        $slug = $slugger->slug(strtolower($title))->toString();

        // Split by dashes (slugger replaces spaces with dashes)
        $words = explode('-', $slug);

        // Filter out stop words
        $filteredWords = array_filter($words, function ($word) use ($stopWords) {
            return !in_array($word, $stopWords) && !empty($word);
        });

        // Rejoin
        return implode('-', $filteredWords);
    }
}

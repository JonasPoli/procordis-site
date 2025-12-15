<?php

namespace App\Controller;

use App\Repository\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/pagina')]
class PageController extends AbstractController
{
    #[Route('/{slug}', name: 'app_page_show', methods: ['GET'])]
    public function show(string $slug, PageRepository $pageRepository): Response
    {
        $page = $pageRepository->findOneBy(['slug' => $slug, 'isActive' => true]);

        if (!$page) {
            throw $this->createNotFoundException('Página não encontrada.');
        }

        return $this->render('page/show.html.twig', [
            'page' => $page,
        ]);
    }
}

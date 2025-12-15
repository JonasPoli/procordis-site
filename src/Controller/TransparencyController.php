<?php

namespace App\Controller;

use App\Repository\TransparencyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/transparencia')]
class TransparencyController extends AbstractController
{
    #[Route('/', name: 'app_transparency_index', methods: ['GET'])]
    public function index(TransparencyRepository $transparencyRepository): Response
    {
        return $this->render('transparency/index.html.twig', [
            'transparencies' => $transparencyRepository->findActive(),
        ]);
    }

    #[Route('/{slug}', name: 'app_transparency_show', methods: ['GET'])]
    public function show(string $slug, TransparencyRepository $transparencyRepository): Response
    {
        $transparency = $transparencyRepository->findOneBy(['slug' => $slug, 'isActive' => true]);

        if (!$transparency) {
            throw $this->createNotFoundException('Item de transparência não encontrado.');
        }

        return $this->render('transparency/show.html.twig', [
            'transparency' => $transparency,
        ]);
    }
}

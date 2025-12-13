<?php

namespace App\Controller;

use App\Repository\NewsRepository;
use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(NewsRepository $newsRepository, ServiceRepository $serviceRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'news' => $newsRepository->findBy([], ['publishedAt' => 'DESC'], 3),
            'services' => $serviceRepository->findBy([], ['title' => 'ASC']),
        ]);
    }

    #[Route('/sobre', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('home/about.html.twig');
    }
}

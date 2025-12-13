<?php

namespace App\Controller;

use App\Repository\NewsRepository;
use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    #[Route('/pesquisa', name: 'app_search')]
    public function index(Request $request, NewsRepository $newsRepository, ServiceRepository $serviceRepository): Response
    {
        $query = $request->query->get('q');
        $newsResults = [];
        $serviceResults = [];

        if ($query) {
            $newsResults = $newsRepository->search($query);
            $serviceResults = $serviceRepository->search($query);
        }

        return $this->render('search/index.html.twig', [
            'query' => $query,
            'news' => $newsResults,
            'services' => $serviceResults,
        ]);
    }
}

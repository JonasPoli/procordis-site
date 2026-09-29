<?php

namespace App\Controller\Admin;

use App\Repository\NewsRepository;
use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_JORNALISTA')]
class DashboardController extends AbstractController
{
    public function __construct(
        private NewsRepository $newsRepository,
        private ServiceRepository $serviceRepository
    ) {
    }

    #[Route('/', name: 'admin_dashboard')]
    public function index(): Response
    {
        // Jornalistas não têm acesso à visão geral: vão direto para as notícias.
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_news_index');
        }

        return $this->render('admin/dashboard/index.html.twig', [
            'newsCount' => $this->newsRepository->count([]),
            'servicesCount' => $this->serviceRepository->count([]),
        ]);
    }
}

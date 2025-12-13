<?php

namespace App\Controller\Admin;

use App\Repository\NewsRepository;
use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
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
        return $this->render('admin/dashboard/index.html.twig', [
            'newsCount' => $this->newsRepository->count([]),
            'servicesCount' => $this->serviceRepository->count([]),
        ]);
    }
}

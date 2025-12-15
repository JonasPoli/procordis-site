<?php

namespace App\Controller;

use App\Repository\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class PartialController extends AbstractController
{
    public function footerPages(PageRepository $pageRepository): Response
    {
        return $this->render('partials/footer_pages.html.twig', [
            'pages' => $pageRepository->findActive(),
        ]);
    }

    public function footerServices(\App\Repository\ServiceRepository $serviceRepository): Response
    {
        return $this->render('partials/footer_services.html.twig', [
            'services' => $serviceRepository->findBy([], ['title' => 'ASC'], 5),
        ]);
    }
}

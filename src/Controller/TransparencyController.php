<?php

namespace App\Controller;

use App\Repository\TransparencyDocRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TransparencyController extends AbstractController
{
    #[Route('/transparencia', name: 'app_transparency')]
    public function index(TransparencyDocRepository $docRepository): Response
    {
        $docs = $docRepository->findBy([], ['year' => 'DESC', 'month' => 'DESC']);

        // Group by Year
        $groupedDocs = [];
        foreach ($docs as $doc) {
            $groupedDocs[$doc->getYear()][] = $doc;
        }

        return $this->render('transparency/index.html.twig', [
            'grouped_docs' => $groupedDocs,
        ]);
    }
}

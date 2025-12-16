<?php

namespace App\Controller;

use App\Repository\SpecialtyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/especialidades')]
class SpecialtyController extends AbstractController
{
    #[Route('/', name: 'app_specialty_index', methods: ['GET'])]
    public function index(SpecialtyRepository $specialtyRepository): Response
    {
        return $this->render('specialty/index.html.twig', [
            'specialties' => $specialtyRepository->findBy(['active' => true], ['sortOrder' => 'ASC']),
        ]);
    }

    #[Route('/{slug}', name: 'app_specialty_show', methods: ['GET'])]
    public function show(string $slug, SpecialtyRepository $specialtyRepository): Response
    {
        $specialty = $specialtyRepository->findOneBy(['slug' => $slug, 'active' => true]);

        if (!$specialty) {
            throw $this->createNotFoundException('Especialidade não encontrada.');
        }

        return $this->render('specialty/show.html.twig', [
            'specialty' => $specialty,
        ]);
    }
}

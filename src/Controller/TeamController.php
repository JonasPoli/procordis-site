<?php

namespace App\Controller;

use App\Repository\DoctorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TeamController extends AbstractController
{
    #[Route('/corpo-clinico', name: 'app_team_list')]
    public function index(DoctorRepository $doctorRepository): Response
    {
        return $this->render('team/index.html.twig', [
            'doctors' => $doctorRepository->findAll(),
        ]);
    }
}

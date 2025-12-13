<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReplicaController extends AbstractController
{
    #[Route('/replica', name: 'app_replica')]
    public function index(): Response
    {
        return $this->render('replica/index.html.twig', [
            'controller_name' => 'ReplicaController',
        ]);
    }
}

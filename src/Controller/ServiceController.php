<?php

namespace App\Controller;

use App\Entity\Service;
use App\Repository\GeneralDataRepository;
use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/servicos')]
class ServiceController extends AbstractController
{
    #[Route('/', name: 'app_service_index', methods: ['GET'])]
    public function index(ServiceRepository $serviceRepository, GeneralDataRepository $generalDataRepository): Response
    {
        return $this->render('service/index.html.twig', [
            'services' => $serviceRepository->findAll(),
            'generalData' => $generalDataRepository->findOneBy([]),
        ]);
    }

    #[Route('/{slug}', name: 'app_service_show', methods: ['GET'])]
    public function show(string $slug, ServiceRepository $serviceRepository, GeneralDataRepository $generalDataRepository): Response
    {
        $service = $serviceRepository->findOneBy(['slug' => $slug]);

        if (!$service) {
            throw $this->createNotFoundException('Serviço não encontrado');
        }

        // Fetch other services for the sidebar/suggestions
        $otherServices = $serviceRepository->createQueryBuilder('s')
            ->where('s.id != :currentId')
            ->setParameter('currentId', $service->getId())
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('service/show.html.twig', [
            'service' => $service,
            'otherServices' => $otherServices,
            'generalData' => $generalDataRepository->findOneBy([]),
        ]);
    }
}

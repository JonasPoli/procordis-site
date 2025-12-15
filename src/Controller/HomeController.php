<?php

namespace App\Controller;

use App\Repository\GeneralDataRepository;
use App\Repository\NewsRepository;
use App\Repository\ServiceRepository;
use App\Repository\SpecialtyRepository;
use App\Repository\DoctorRepository;
use App\Repository\TestimonyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        GeneralDataRepository $generalDataRepository,
        ServiceRepository $serviceRepository,
        SpecialtyRepository $specialtyRepository,
        DoctorRepository $doctorRepository,
        NewsRepository $newsRepository,
        TestimonyRepository $testimonyRepository,
        \App\Repository\HomeBannerRepository $homeBannerRepository,
        \App\Repository\AboutPageRepository $aboutPageRepository
    ): Response
    {
        return $this->render('home/index.html.twig', [
            'generalData' => $generalDataRepository->findOneBy([]),
            'services' => $serviceRepository->findBy([], ['id' => 'ASC'], 4),
            'specialties' => $specialtyRepository->findActive(),
            'doctors' => $doctorRepository->findBy([], ['id' => 'ASC']),
            'latestNews' => $newsRepository->findRecent(3),
            'testimonies' => $testimonyRepository->findActive(10),
            'banners' => $homeBannerRepository->findActive(),
            'about' => $aboutPageRepository->getPage(),
        ]);
    }

    #[Route('/sobre', name: 'app_about')]
    public function about(
        \App\Repository\AboutPageRepository $aboutPageRepository,
        \App\Repository\TimelineItemRepository $timelineItemRepository,
        GeneralDataRepository $generalDataRepository
    ): Response
    {
        return $this->render('home/about.html.twig', [
            'about' => $aboutPageRepository->getPage(),
            'timelineItems' => $timelineItemRepository->findBy([], ['sortOrder' => 'ASC']),
            'generalData' => $generalDataRepository->findOneBy([]),
        ]);
    }
}

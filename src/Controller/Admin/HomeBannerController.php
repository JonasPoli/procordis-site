<?php

namespace App\Controller\Admin;

use App\Entity\HomeBanner;
use App\Form\HomeBannerType;
use App\Repository\HomeBannerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/banners')]
#[IsGranted('ROLE_ADMIN')]
class HomeBannerController extends AbstractController
{
    #[Route('/', name: 'admin_banner_index', methods: ['GET'])]
    public function index(HomeBannerRepository $homeBannerRepository): Response
    {
        return $this->render('admin/home_banner/index.html.twig', [
            'banners' => $homeBannerRepository->findBy([], ['sortOrder' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'admin_banner_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $banner = new HomeBanner();
        $form = $this->createForm(HomeBannerType::class, $banner);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($banner);
            $entityManager->flush();

            return $this->redirectToRoute('admin_banner_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/home_banner/new.html.twig', [
            'banner' => $banner,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_banner_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, HomeBanner $banner, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(HomeBannerType::class, $banner);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_banner_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/home_banner/edit.html.twig', [
            'banner' => $banner,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_banner_delete', methods: ['POST'])]
    public function delete(Request $request, HomeBanner $banner, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$banner->getId(), $request->request->get('_token'))) {
            $entityManager->remove($banner);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_banner_index', [], Response::HTTP_SEE_OTHER);
    }
}

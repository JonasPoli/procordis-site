<?php

namespace App\Controller\Admin;

use App\Form\AboutPageType;
use App\Repository\AboutPageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/about')]
#[IsGranted('ROLE_ADMIN')]
class AboutPageController extends AbstractController
{
    #[Route('/', name: 'admin_about_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AboutPageRepository $aboutPageRepository, EntityManagerInterface $entityManager): Response
    {
        $aboutPage = $aboutPageRepository->getPage();
        $form = $this->createForm(AboutPageType::class, $aboutPage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$aboutPage->getId()) {
                $entityManager->persist($aboutPage);
            }
            $entityManager->flush();

            $this->addFlash('success', 'Conteúdo atualizado com sucesso!');

            return $this->redirectToRoute('admin_about_edit');
        }

        return $this->render('admin/about_page/edit.html.twig', [
            'about_page' => $aboutPage,
            'form' => $form,
        ]);
    }
}

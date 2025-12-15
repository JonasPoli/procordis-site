<?php

namespace App\Controller\Admin;

use App\Entity\Testimony;
use App\Form\TestimonyType;
use App\Repository\TestimonyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/testimony')]
#[IsGranted('ROLE_ADMIN')]
class TestimonyController extends AbstractController
{
    #[Route('/', name: 'admin_testimony_index', methods: ['GET'])]
    public function index(TestimonyRepository $testimonyRepository): Response
    {
        return $this->render('admin/testimony/index.html.twig', [
            'testimonies' => $testimonyRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'admin_testimony_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $testimony = new Testimony();
        $form = $this->createForm(TestimonyType::class, $testimony);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($testimony);
            $entityManager->flush();

            $this->addFlash('success', 'Depoimento criado com sucesso!');

            return $this->redirectToRoute('admin_testimony_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/testimony/new.html.twig', [
            'testimony' => $testimony,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_testimony_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Testimony $testimony, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TestimonyType::class, $testimony);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Depoimento atualizado com sucesso!');

            return $this->redirectToRoute('admin_testimony_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/testimony/edit.html.twig', [
            'testimony' => $testimony,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_testimony_delete', methods: ['POST'])]
    public function delete(Request $request, Testimony $testimony, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $testimony->getId(), $request->request->get('_token'))) {
            $entityManager->remove($testimony);
            $entityManager->flush();
            $this->addFlash('success', 'Depoimento excluído com sucesso!');
        }

        return $this->redirectToRoute('admin_testimony_index', [], Response::HTTP_SEE_OTHER);
    }
}

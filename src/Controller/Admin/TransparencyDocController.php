<?php

namespace App\Controller\Admin;

use App\Entity\TransparencyDoc;
use App\Form\TransparencyDocType;
use App\Repository\TransparencyDocRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/transparency')]
#[IsGranted('ROLE_ADMIN')]
class TransparencyDocController extends AbstractController
{
    #[Route('/', name: 'admin_transparency_index', methods: ['GET'])]
    public function index(TransparencyDocRepository $docRepository): Response
    {
        return $this->render('admin/transparency/index.html.twig', [
            'docs' => $docRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_transparency_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $doc = new TransparencyDoc();
        $form = $this->createForm(TransparencyDocType::class, $doc);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($doc);
            $entityManager->flush();
            $this->addFlash('success', 'Documento criado com sucesso!');
            return $this->redirectToRoute('admin_transparency_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/transparency/new.html.twig', [
            'doc' => $doc,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_transparency_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TransparencyDoc $doc, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TransparencyDocType::class, $doc);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Documento atualizado com sucesso!');
            return $this->redirectToRoute('admin_transparency_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/transparency/edit.html.twig', [
            'doc' => $doc,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_transparency_delete', methods: ['POST'])]
    public function delete(Request $request, TransparencyDoc $doc, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $doc->getId(), $request->request->get('_token'))) {
            $entityManager->remove($doc);
            $entityManager->flush();
            $this->addFlash('success', 'Documento excluído com sucesso!');
        }

        return $this->redirectToRoute('admin_transparency_index', [], Response::HTTP_SEE_OTHER);
    }
}

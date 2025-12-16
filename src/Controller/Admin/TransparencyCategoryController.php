<?php

namespace App\Controller\Admin;

use App\Entity\Transparency;
use App\Form\TransparencyType;
use App\Repository\TransparencyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/transparency-category')]
#[IsGranted('ROLE_ADMIN')]
class TransparencyCategoryController extends AbstractController
{
    #[Route('/', name: 'admin_transparency_category_index', methods: ['GET'])]
    public function index(TransparencyRepository $transparencyRepository): Response
    {
        return $this->render('admin/transparency_category/index.html.twig', [
            'categories' => $transparencyRepository->findBy([], ['title' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'admin_transparency_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $transparency = new Transparency();
        $form = $this->createForm(TransparencyType::class, $transparency);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (empty($transparency->getSlug())) {
                $transparency->setSlug(strtolower($slugger->slug($transparency->getTitle())->toString()));
            }
            $entityManager->persist($transparency);
            $entityManager->flush();

            $this->addFlash('success', 'Categoria de transparência criada com sucesso!');

            return $this->redirectToRoute('admin_transparency_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/transparency_category/new.html.twig', [
            'transparency' => $transparency,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_transparency_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Transparency $transparency, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(TransparencyType::class, $transparency);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (empty($transparency->getSlug())) {
                $transparency->setSlug(strtolower($slugger->slug($transparency->getTitle())->toString()));
            }
            $entityManager->flush();

            $this->addFlash('success', 'Categoria de transparência atualizada com sucesso!');

            return $this->redirectToRoute('admin_transparency_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/transparency_category/edit.html.twig', [
            'transparency' => $transparency,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_transparency_category_delete', methods: ['POST'])]
    public function delete(Request $request, Transparency $transparency, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $transparency->getId(), $request->request->get('_token'))) {
            $entityManager->remove($transparency);
            $entityManager->flush();
            $this->addFlash('success', 'Categoria de transparência excluída com sucesso!');
        }

        return $this->redirectToRoute('admin_transparency_category_index', [], Response::HTTP_SEE_OTHER);
    }
}

<?php

namespace App\Controller\Admin;

use App\Entity\PageSeo;
use App\Form\PageSeoType;
use App\Repository\PageSeoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/page-seo')]
#[IsGranted('ROLE_ADMIN')]
class PageSeoController extends AbstractController
{
    #[Route('/edit', name: 'admin_page_seo_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PageSeoRepository $repo, EntityManagerInterface $entityManager): Response
    {
        $pageSeo = $repo->findOneBy([]) ?? new PageSeo();

        $form = $this->createForm(PageSeoType::class, $pageSeo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($pageSeo);
            $entityManager->flush();

            $this->addFlash('success', 'SEO das Páginas atualizado com sucesso!');

            return $this->redirectToRoute('admin_page_seo_edit', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/page_seo/edit.html.twig', [
            'page_seo' => $pageSeo,
            'form' => $form,
        ]);
    }
}

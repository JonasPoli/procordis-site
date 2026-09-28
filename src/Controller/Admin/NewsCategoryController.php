<?php

namespace App\Controller\Admin;

use App\Entity\NewsCategory;
use App\Form\NewsCategoryType;
use App\Repository\NewsCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/news-category')]
#[IsGranted('ROLE_JORNALISTA')]
class NewsCategoryController extends AbstractController
{
    use AdminAjaxTrait;
    use StatusFilterTrait;

    #[Route('/', name: 'admin_news_category_index', methods: ['GET'])]
    public function index(Request $request, NewsCategoryRepository $newsCategoryRepository): Response
    {
        [$activeFilter, $status] = $this->resolveStatusFilter($request);

        return $this->render('admin/news_category/index.html.twig', [
            'news_categories' => $newsCategoryRepository->findForAdmin($activeFilter),
            'status' => $status,
            'statusCounts' => $newsCategoryRepository->countByStatus(),
        ]);
    }

    /**
     * Alternância rápida de "Ativo" direto na listagem (AJAX).
     */
    #[Route('/{id}/toggle-active', name: 'admin_news_category_toggle_active', methods: ['POST'])]
    public function toggleActive(Request $request, NewsCategory $newsCategory, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($error = $this->checkAjaxCsrf($request)) {
            return $error;
        }

        $newsCategory->setActive(!$newsCategory->isActive());
        $entityManager->flush();

        return new JsonResponse([
            'active' => $newsCategory->isActive(),
            'message' => $newsCategory->isActive()
                ? 'Categoria ativada: ela volta a aparecer no site.'
                : 'Categoria desativada: ela some do site (as notícias continuam visíveis).',
        ]);
    }

    #[Route('/new', name: 'admin_news_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $newsCategory = new NewsCategory();
        $form = $this->createForm(NewsCategoryType::class, $newsCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($newsCategory);
            $entityManager->flush();

            return $this->redirectToRoute('admin_news_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/news_category/new.html.twig', [
            'news_category' => $newsCategory,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_news_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, NewsCategory $newsCategory, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(NewsCategoryType::class, $newsCategory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_news_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/news_category/edit.html.twig', [
            'news_category' => $newsCategory,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_news_category_delete', methods: ['POST'])]
    public function delete(Request $request, NewsCategory $newsCategory, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$newsCategory->getId(), $request->request->get('_token'))) {
            $entityManager->remove($newsCategory);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_news_category_index', [], Response::HTTP_SEE_OTHER);
    }
}

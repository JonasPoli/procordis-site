<?php

namespace App\Controller\Admin;

use App\Entity\TimelineItem;
use App\Form\TimelineItemType;
use App\Repository\TimelineItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/timeline')]
#[IsGranted('ROLE_ADMIN')]
class TimelineItemController extends AbstractController
{
    #[Route('/', name: 'admin_timeline_index', methods: ['GET'])]
    public function index(TimelineItemRepository $timelineItemRepository): Response
    {
        return $this->render('admin/timeline_item/index.html.twig', [
            'items' => $timelineItemRepository->findBy([], ['sortOrder' => 'ASC', 'year' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'admin_timeline_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $item = new TimelineItem();
        $form = $this->createForm(TimelineItemType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($item);
            $entityManager->flush();

            return $this->redirectToRoute('admin_timeline_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/timeline_item/new.html.twig', [
            'item' => $item,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_timeline_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TimelineItem $item, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TimelineItemType::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_timeline_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/timeline_item/edit.html.twig', [
            'item' => $item,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_timeline_delete', methods: ['POST'])]
    public function delete(Request $request, TimelineItem $item, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$item->getId(), $request->request->get('_token'))) {
            $entityManager->remove($item);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_timeline_index', [], Response::HTTP_SEE_OTHER);
    }
}

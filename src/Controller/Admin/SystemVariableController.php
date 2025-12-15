<?php

namespace App\Controller\Admin;

use App\Entity\SystemVariable;
use App\Form\SystemVariableType;
use App\Repository\SystemVariableRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/system-variables')]
#[IsGranted('ROLE_ADMIN')]
class SystemVariableController extends AbstractController
{
    #[Route('/', name: 'admin_system_variable_index', methods: ['GET'])]
    public function index(SystemVariableRepository $systemVariableRepository): Response
    {
        return $this->render('admin/system_variable/index.html.twig', [
            'variables' => $systemVariableRepository->findAll(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_system_variable_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SystemVariable $systemVariable, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SystemVariableType::class, $systemVariable);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_system_variable_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/system_variable/edit.html.twig', [
            'variable' => $systemVariable,
            'form' => $form,
        ]);
    }
}

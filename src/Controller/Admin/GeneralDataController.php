<?php

namespace App\Controller\Admin;

use App\Entity\GeneralData;
use App\Form\GeneralDataType;
use App\Repository\GeneralDataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/general-data')]
#[IsGranted('ROLE_ADMIN')]
class GeneralDataController extends AbstractController
{
    #[Route('/edit', name: 'admin_general_data_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, GeneralDataRepository $repo, EntityManagerInterface $entityManager): Response
    {
        $generalData = $repo->findOneBy([]) ?? new GeneralData();

        $form = $this->createForm(GeneralDataType::class, $generalData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($generalData);
            $entityManager->flush();

            $this->addFlash('success', 'Dados gerais atualizados com sucesso!');

            return $this->redirectToRoute('admin_general_data_edit', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/general_data/edit.html.twig', [
            'general_data' => $generalData,
            'form' => $form,
        ]);
    }
}

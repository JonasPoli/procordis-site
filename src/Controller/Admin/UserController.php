<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Gestão dos usuários do painel. Somente o administrador geral acessa.
 */
#[Route('/admin/usuarios')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('/', name: 'admin_user_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $this->userRepository->findAllOrdered(),
        ]);
    }

    #[Route('/novo', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, [
            'require_password' => true,
            'access_type' => User::ROLE_JORNALISTA,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setAccessType((string) $form->get('accessType')->getData());
            $this->applyPassword($user, $form);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('Usuário %s criado com sucesso!', $user->getEmail()));

            return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ], new Response(null, $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/{id}/editar', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user): Response
    {
        $isSelf = $this->isCurrentUser($user);
        $form = $this->createForm(UserType::class, $user, [
            'require_password' => false,
            'access_type' => $user->getAccessType(),
            'lock_access_type' => $isSelf,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$isSelf) {
                $newType = (string) $form->get('accessType')->getData();

                if ($user->isAdmin() && User::ROLE_ADMIN !== $newType && $this->userRepository->countAdmins() <= 1) {
                    $form->get('accessType')->addError(new FormError('Este é o único administrador. Cadastre outro administrador antes de alterar o tipo deste usuário.'));

                    return $this->render('admin/user/edit.html.twig', [
                        'user' => $user,
                        'form' => $form,
                    ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
                }

                $user->setAccessType($newType);
            }

            $this->applyPassword($user, $form);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('Usuário %s atualizado com sucesso!', $user->getEmail()));

            return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ], new Response(null, $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/{id}', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user): Response
    {
        if (!$this->isCsrfTokenValid('delete_user'.$user->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'Não foi possível validar a solicitação. Recarregue a página e tente novamente.');

            return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($this->isCurrentUser($user)) {
            $this->addFlash('error', 'Você não pode excluir o seu próprio usuário.');

            return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($user->isAdmin() && $this->userRepository->countAdmins() <= 1) {
            $this->addFlash('error', 'Não é possível excluir o único administrador.');

            return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
        }

        $email = $user->getEmail();
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Usuário %s excluído com sucesso!', $email));

        return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
    }

    private function applyPassword(User $user, FormInterface $form): void
    {
        $plainPassword = $form->get('plainPassword')->getData();

        if (\is_string($plainPassword) && '' !== $plainPassword) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        }
    }

    private function isCurrentUser(User $user): bool
    {
        $current = $this->getUser();

        return $current instanceof User && $current->getId() === $user->getId();
    }
}

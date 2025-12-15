<?php

namespace App\Controller\Admin;

use App\Repository\ContactMessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/messages')]
#[IsGranted('ROLE_ADMIN')]
class ContactMessageController extends AbstractController
{
    #[Route('/', name: 'admin_contact_message_index', methods: ['GET'])]
    public function index(ContactMessageRepository $messageRepository): Response
    {
        return $this->render('admin/contact_message/index.html.twig', [
            'messages' => $messageRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }
}

<?php

namespace App\Controller\Api;

use App\Entity\NewsletterSubscriber;
use App\Repository\NewsletterSubscriberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/newsletter')]
class NewsletterApiController extends AbstractController
{
    #[Route('', name: 'api_newsletter_submit', methods: ['POST'])]
    public function submit(
        Request $request,
        EntityManagerInterface $entityManager,
        NewsletterSubscriberRepository $repository
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['email'])) {
            return $this->json(['error' => 'E-mail obrigatório.'], 400);
        }

        $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
        if (!$email) {
            return $this->json(['error' => 'E-mail inválido.'], 400);
        }

        // Check duplicate
        if ($repository->findOneBy(['email' => $email])) {
            return $this->json(['error' => 'Duplicate: Este e-mail já está cadastrado.'], 400);
        }

        $subscriber = new NewsletterSubscriber();
        $subscriber->setEmail($email);
        $subscriber->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($subscriber);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }
}

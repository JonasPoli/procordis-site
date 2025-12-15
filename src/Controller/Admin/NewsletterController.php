<?php

namespace App\Controller\Admin;

use App\Repository\NewsletterSubscriberRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/newsletter')]
#[IsGranted('ROLE_ADMIN')]
class NewsletterController extends AbstractController
{
    #[Route('/', name: 'admin_newsletter_index', methods: ['GET'])]
    public function index(NewsletterSubscriberRepository $subscriberRepository): Response
    {
        return $this->render('admin/newsletter/index.html.twig', [
            'subscribers' => $subscriberRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/export', name: 'admin_newsletter_export', methods: ['GET'])]
    public function export(NewsletterSubscriberRepository $subscriberRepository): Response
    {
        $subscribers = $subscriberRepository->findAll();

        $response = new StreamedResponse(function () use ($subscribers) {
            $handle = fopen('php://output', 'w+');
            fputcsv($handle, ['ID', 'Email', 'Data de Inscrição']);

            foreach ($subscribers as $subscriber) {
                fputcsv($handle, [
                    $subscriber->getId(),
                    $subscriber->getEmail(),
                    $subscriber->getCreatedAt()->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="newsletter_subscribers.csv"');

        return $response;
    }
}

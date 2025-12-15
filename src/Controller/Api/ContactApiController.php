<?php

namespace App\Controller\Api;

use App\Entity\ContactMessage;
use App\Repository\SystemVariableRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/contact')]
class ContactApiController extends AbstractController
{
    #[Route('', name: 'api_contact_submit', methods: ['POST'])]
    public function submit(
        Request $request,
        EntityManagerInterface $entityManager,
        SystemVariableRepository $systemVariableRepository,
        LoggerInterface $logger
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['name'], $data['email'], $data['message'])) {
            return $this->json(['error' => 'Dados inválidos.'], 400);
        }

        try {
            $message = new ContactMessage();
            $message->setName($data['name']);
            $message->setEmail($data['email']);
            $message->setSubject($data['subject'] ?? 'Contato pelo Site');
            $message->setMessage($data['message']);
            // Check if phone exists in data or entity, entity has setPhone? Yes.
            if (isset($data['phone'])) {
                 $message->setPhone($data['phone']);
            }
            // $message->setIpAddress($request->getClientIp()); // Entity does not have ipAddress yet
            $message->setCreatedAt(new \DateTimeImmutable());
            // $message->setStatus('new'); // If standard entity has default, fine.

            $entityManager->persist($message);
            $entityManager->flush();

            // Send Email Notification (Log only for now if mailer not injected, or use mailer if available)
            // The previous code used SystemVariableRepository to get recipients and logged. We keep that safe.
            try {
                $recipients = $systemVariableRepository->getValue('contact_email_recipients');
                if ($recipients) {
                    $recipientList = array_map('trim', explode(',', $recipients));
                    foreach ($recipientList as $email) {
                        $logger->info("Sending contact email to: {$email} regarding '{$message->getSubject()}' from {$message->getName()}");
                    }
                }
            } catch (\Exception $emailEx) {
                $logger->warning("Failed to check recipients or log email: " . $emailEx->getMessage());
            }

            return $this->json(['success' => true]);

        } catch (\Exception $e) {
            $logger->critical('API Contact Error: ' . $e->getMessage());
            return $this->json(['error' => 'Erro ao processar mensagem: ' . $e->getMessage()], 500);
        }
    }
}

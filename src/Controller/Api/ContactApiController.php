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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
#[Route('/api/contact')]
class ContactApiController extends AbstractController
{
    #[Route('', name: 'api_contact_submit', methods: ['POST'])]
    public function submit(
        Request $request,
        EntityManagerInterface $entityManager,
        SystemVariableRepository $systemVariableRepository,
        LoggerInterface $logger,
        MailerInterface $mailer
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

            // Send Email Notification
            try {
                $recipientsStr = $systemVariableRepository->getValue('contact_email_recipients');
                if ($recipientsStr) {
                    $recipientList = array_map('trim', preg_split('/\r\n|\r|\n|,/', $recipientsStr));
                    foreach ($recipientList as $rcpt) {
                        if (filter_var($rcpt, FILTER_VALIDATE_EMAIL)) {
                            $email = (new TemplatedEmail())
                                ->from('noreply@wab.com.br')
                                ->to($rcpt)
                                ->subject('[Procordis] Novo Contato: ' . $message->getSubject())
                                ->htmlTemplate('emails/contact.html.twig')
                                ->context([
                                    'contact' => $message
                                ]);

                            $mailer->send($email);
                            $logger->info("Email enviado via wmailer para: {$rcpt}");
                        }
                    }
                }
            } catch (\Exception $emailEx) {
                $logger->warning("Erro ao enviar email ou recuperar destinatarios: " . $emailEx->getMessage());
            }

            return $this->json(['success' => true]);

        } catch (\Exception $e) {
            $logger->critical('API Contact Error: ' . $e->getMessage());
            return $this->json(['error' => 'Erro ao processar mensagem: ' . $e->getMessage()], 500);
        }
    }
}

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
use Symfony\Component\Mime\Email;
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
                $recipients = $systemVariableRepository->getValue('contact_email_recipients');
                if ($recipients) {
                    $recipientList = array_map('trim', preg_split('/\r\n|\r|\n|,/', $recipients));
                    
                    $emailBody = "Você tem uma nova mensagem de contato do site.\n\n" .
                                 "Nome: {$message->getName()}\n" .
                                 "E-mail: {$message->getEmail()}\n";
                    if ($message->getPhone()) {
                        $emailBody .= "Telefone: {$message->getPhone()}\n";
                    }
                    $emailBody .= "Assunto: {$message->getSubject()}\n" .
                                 "Mensagem:\n{$message->getMessage()}\n";

                    $email = (new Email())
                        ->from('noreply@wab.com.br')
                        ->subject('Novo Contato do Site: ' . $message->getSubject())
                        ->text($emailBody);

                    foreach ($recipientList as $rcpt) {
                        if (filter_var($rcpt, FILTER_VALIDATE_EMAIL)) {
                            $email->addTo($rcpt);
                        }
                    }

                    if (count($email->getTo()) > 0) {
                        $mailer->send($email);
                        $logger->info("Email enviado via wmailer para: " . implode(', ', array_map(function($add) { return $add->getAddress(); }, $email->getTo())));
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

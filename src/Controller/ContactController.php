<?php

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

class ContactController extends AbstractController
{
    #[Route('/contato', name: 'app_contact')]
    public function index(Request $request, MailerInterface $mailer, LoggerInterface $logger, \Doctrine\ORM\EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // 1. Save to Database
            try {
                $contactMessage = new \App\Entity\ContactMessage();
                $contactMessage->setName($data['name']);
                $contactMessage->setEmail($data['email']);
                // ContactType doesn't have phone, so we skip it or if it's added later
                if (isset($data['phone'])) {
                    $contactMessage->setPhone($data['phone']);
                }
                $contactMessage->setSubject($data['subject']);
                $contactMessage->setMessage($data['message']);
                $contactMessage->setCreatedAt(new \DateTimeImmutable());
                $contactMessage->setStatus('new');

                $entityManager->persist($contactMessage);
                $entityManager->flush();

            } catch (\Exception $e) {
                $logger->critical('Erro ao salvar mensagem de contato no banco: ' . $e->getMessage());
                $this->addFlash('error', 'Ocorreu um erro ao salvar sua mensagem. Tente novamente.');
                return $this->redirectToRoute('app_contact');
            }

            // 2. Send Email (Best Effort)
            try {
                $email = (new Email())
                    ->from('no-reply@procordis.org.br') // System email
                    ->replyTo($data['email']) // User email for reply
                    ->to('contato@procordis.com.br')
                    ->subject('Novo contato pelo site: ' . $data['subject'])
                    ->html(sprintf(
                        '<p><strong>Nome:</strong> %s</p>
                         <p><strong>Email:</strong> %s</p>
                         <p><strong>Assunto:</strong> %s</p>
                         <p><strong>Mensagem:</strong><br>%s</p>',
                        htmlspecialchars($data['name']),
                        htmlspecialchars($data['email']),
                        htmlspecialchars($data['subject']),
                        nl2br(htmlspecialchars($data['message']))
                    ));

                $mailer->send($email);
            } catch (\Exception $e) {
                // Log email error but don't fail the user request since data is safe in DB
                $logger->error('Erro ao enviar email de contato (mas salvo no banco): ' . $e->getMessage());
            }

            $this->addFlash('success', 'Sua mensagem foi enviada com sucesso! Em breve entraremos em contato.');
            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

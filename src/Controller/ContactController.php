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
    public function index(Request $request, MailerInterface $mailer, LoggerInterface $logger): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $email = (new Email())
                ->from($data['email']) // Note: In production, 'from' should often be a verified system email, with Reply-To as the user's email.
                ->to('contato@procordis.com.br') // Configure this properly via .env if needed, or get from GeneralData
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

            try {
                $mailer->send($email);
                $this->addFlash('success', 'Sua mensagem foi enviada com sucesso! Em breve entraremos em contato.');
            } catch (\Exception $e) {
                $logger->error('Erro ao enviar email de contato: ' . $e->getMessage());
                $this->addFlash('error', 'Ocorreu um erro ao enviar sua mensagem. Por favor, tente novamente mais tarde ou entre em contato pelo telefone.');
            }

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

<?php

namespace App\Tests\Controller;

use App\Entity\ContactMessage;
use App\Entity\SystemVariable;
use App\Tests\Support\AppWebTestCase;
use Symfony\Component\Mime\Email;

/**
 * Formulário de contato da home (POST /api/contact): regressão da leitura compartilhada
 * de contact_email_recipients (SystemVariableRepository::getEmailList()).
 */
final class ContactApiControllerTest extends AppWebTestCase
{
    public function testSendsOneEmailPerRecipient(): void
    {
        $variable = (new SystemVariable())
            ->setVariableKey('contact_email_recipients')
            ->setVariableValue("primeiro@procordis.org.br\r\nnão é e-mail\r\nsegundo@procordis.org.br");
        $this->em()->persist($variable);
        $this->em()->flush();

        $this->client->request('POST', '/api/contact', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'Maria Visitante',
            'email' => 'visitante@exemplo.com',
            'subject' => 'Dúvida',
            'message' => 'Mensagem pela home.',
        ], \JSON_THROW_ON_ERROR));

        self::assertResponseIsSuccessful();
        self::assertSame(['success' => true], json_decode((string) $this->client->getResponse()->getContent(), true));
        self::assertSame(1, $this->em()->getRepository(ContactMessage::class)->count([]));

        $sent = [];
        foreach (self::getMailerEvents() as $event) {
            $email = $event->getMessage();
            if (!$event->isQueued() && $email instanceof Email) {
                self::assertCount(1, $email->getTo());
                self::assertEmailHeaderSame($email, 'Subject', '[Procordis] Novo Contato: Dúvida');
                $sent[] = $email->getTo()[0]->getAddress();
            }
        }

        self::assertEmailCount(2);
        self::assertSame(['primeiro@procordis.org.br', 'segundo@procordis.org.br'], $sent);
    }
}

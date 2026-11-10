<?php

namespace App\Tests\Controller;

use App\Entity\ContactMessage;
use App\Entity\SystemVariable;
use App\Tests\Support\AppWebTestCase;
use Monolog\Handler\TestHandler;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

/**
 * Formulário da página /contato: grava a mensagem e envia um e-mail a cada endereço válido
 * da variável de sistema contact_email_recipients (a mesma lista do formulário da home).
 * O transporte de e-mail dos testes é o null://: nenhum e-mail real sai.
 */
final class ContactControllerTest extends AppWebTestCase
{
    public function testSendsOneEmailPerRecipient(): void
    {
        $this->setRecipients("primeiro@procordis.org.br\nsegundo@procordis.org.br");

        $this->submitContact();

        self::assertResponseRedirects('/contato');
        self::assertEmailCount(2);

        $messages = $this->sentEmails();
        self::assertSame(['primeiro@procordis.org.br', 'segundo@procordis.org.br'], array_map(
            static fn (Email $email): string => $email->getTo()[0]->getAddress(),
            $messages,
        ));

        foreach ($messages as $email) {
            self::assertCount(1, $email->getTo());
            self::assertEmailHeaderSame($email, 'Subject', 'Novo contato pelo site: Agendamento de consulta');
            self::assertSame('visitante@exemplo.com', $email->getReplyTo()[0]->getAddress());
            self::assertSame('no-reply@procordis.org.br', $email->getFrom()[0]->getAddress());
            self::assertEmailHtmlBodyContains($email, 'Maria Visitante');
            self::assertEmailHtmlBodyContains($email, 'Gostaria de agendar');
        }
    }

    public function testCommaSeparatedListAndInvalidLineIsIgnored(): void
    {
        $this->setRecipients("valido@procordis.org.br, não é e-mail\n\n");

        $this->submitContact();

        self::assertEmailCount(1);
        self::assertEmailAddressContains($this->sentEmails()[0], 'To', 'valido@procordis.org.br');
    }

    public function testEmptyVariableSavesMessageWithoutEmail(): void
    {
        $this->setRecipients('');

        $this->submitContact();

        self::assertResponseRedirects('/contato');
        self::assertEmailCount(0);
        self::assertSame(1, $this->em()->getRepository(ContactMessage::class)->count([]));
        self::assertTrue($this->testLog()->hasWarningThatContains('contact_email_recipients'));

        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'Sua mensagem foi enviada com sucesso! Em breve entraremos em contato.');
    }

    public function testMissingVariableSavesMessageWithoutEmail(): void
    {
        $this->submitContact();

        self::assertResponseRedirects('/contato');
        self::assertEmailCount(0);
        self::assertSame(1, $this->em()->getRepository(ContactMessage::class)->count([]));
    }

    public function testFailureOnFirstRecipientDoesNotStopTheSecond(): void
    {
        $this->setRecipients("recusado@procordis.org.br\nsegundo@procordis.org.br");

        // Mailer de teste: recusa o primeiro endereço e entrega os demais ao transporte null://,
        // que avisa o mesmo despachante de eventos (o assertEmailCount continua valendo).
        $container = static::getContainer();
        $dispatcher = $container->get('event_dispatcher');
        $innerMailer = new Mailer(Transport::fromDsn('null://null', $dispatcher), null, $dispatcher);
        $container->set(MailerInterface::class, new class($innerMailer) implements MailerInterface {
            public function __construct(private readonly MailerInterface $inner)
            {
            }

            public function send(RawMessage $message, ?Envelope $envelope = null): void
            {
                if ($message instanceof Email && 'recusado@procordis.org.br' === $message->getTo()[0]->getAddress()) {
                    throw new TransportException('Destinatário recusado (simulado).');
                }

                $this->inner->send($message, $envelope);
            }
        });

        $this->submitContact();

        self::assertResponseRedirects('/contato');
        self::assertEmailCount(1);
        self::assertEmailAddressContains($this->sentEmails()[0], 'To', 'segundo@procordis.org.br');
        self::assertSame(1, $this->em()->getRepository(ContactMessage::class)->count([]));
        self::assertTrue($this->testLog()->hasErrorThatContains('recusado@procordis.org.br'));
        self::assertFalse($this->testLog()->hasErrorThatContains('segundo@procordis.org.br'));
    }

    public function testOldAddressIsNeverUsed(): void
    {
        $this->setRecipients('contato@procordis.org.br');

        $this->submitContact();

        self::assertEmailCount(1);
        foreach ($this->sentEmails() as $email) {
            $addresses = array_map(
                static fn (Address $address): string => $address->getAddress(),
                [...$email->getTo(), ...$email->getCc(), ...$email->getBcc()],
            );
            self::assertNotContains('contato@procordis.com.br', $addresses);
        }
    }

    private function setRecipients(string $value): void
    {
        $variable = (new SystemVariable())
            ->setVariableKey('contact_email_recipients')
            ->setVariableValue($value);

        $this->em()->persist($variable);
        $this->em()->flush();
    }

    private function submitContact(): void
    {
        $crawler = $this->client->request('GET', '/contato');
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form button[type="submit"]')->form([
            'contact[name]' => 'Maria Visitante',
            'contact[email]' => 'visitante@exemplo.com',
            'contact[subject]' => 'Agendamento de consulta',
            'contact[message]' => "Gostaria de agendar\numa consulta.",
        ]);

        $this->testLog()->clear();
        $this->client->submit($form);
    }

    /**
     * E-mails entregues ao transporte (sem os eventos de "enfileirado" do Mailer).
     *
     * @return list<Email>
     */
    private function sentEmails(): array
    {
        $emails = [];
        foreach (self::getMailerEvents() as $event) {
            if (!$event->isQueued() && $event->getMessage() instanceof Email) {
                $emails[] = $event->getMessage();
            }
        }

        return $emails;
    }

    /**
     * Registros do log em memória (handler "test" do Monolog, só no ambiente de teste).
     */
    private function testLog(): TestHandler
    {
        $handler = static::getContainer()->get('monolog.handler.test');
        self::assertInstanceOf(TestHandler::class, $handler);

        return $handler;
    }
}

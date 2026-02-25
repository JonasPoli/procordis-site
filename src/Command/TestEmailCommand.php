<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:test-email',
    description: 'Envia um e-mail de teste para verificar a integração do Wmailer',
)]
class TestEmailCommand extends Command
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        parent::__construct();
        $this->mailer = $mailer;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $to = 'jonas@wab.com.br';

        $io->text('Iniciando o envio de teste...');

        try {
            $email = (new Email())
                ->from('no-reply@procordis.org.br')
                ->to($to)
                ->subject('Teste de Integração Wmailer via Comando')
                ->text('Este é um e-mail de teste enviado pelo comando do Symfony para verificar a integração do wab-ninjas/wmailer-transport no site da Procordis.');

            $this->mailer->send($email);

            $io->success("Mensagem enviada com sucesso para {$to}!");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error("Erro ao tentar enviar o e-mail: " . $e->getMessage());

            return Command::FAILURE;
        }
    }
}

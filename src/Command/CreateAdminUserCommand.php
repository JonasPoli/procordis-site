<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:admin-user',
    description: 'Cria ou atualiza um usuário do painel (administrador por padrão; use --jornalista para jornalista)',
)]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'The email of the new admin user')
            ->addArgument('password', InputArgument::OPTIONAL, 'The password of the new admin user')
            ->addOption('jornalista', null, InputOption::VALUE_NONE, 'Cria o usuário com o perfil Jornalista (somente notícias, categorias e galeria)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        if (!$email) {
            $email = $io->ask('Email');
        }

        if (!$password) {
            $password = $io->askHidden('Password');
        }

        if (!$email || !$password) {
            $io->error('Email and password are required');
            return Command::FAILURE;
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if ($user) {
            $io->note('User already exists');
        } else {
            $user = new User();
            $user->setEmail($email);
        }

        $isJournalist = (bool) $input->getOption('jornalista');
        $user->setAccessType($isJournalist ? User::ROLE_JORNALISTA : User::ROLE_ADMIN);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user, true); // Assuming repository has save method or using EntityManager

        $io->success(sprintf('Usuário %s (%s) criado/atualizado com sucesso.', $email, $user->getAccessTypeLabel()));

        return Command::SUCCESS;
    }
}

<?php

namespace App\Tests\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Support\AppWebTestCase;

final class UserControllerTest extends AppWebTestCase
{
    public function testAdminCreatesJournalist(): void
    {
        $this->loginAs(User::ROLE_ADMIN);
        $crawler = $this->client->request('GET', '/admin/usuarios/novo');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Criar usuário')->form([
            'user[email]' => 'reporter@procordis.test',
            'user[accessType]' => User::ROLE_JORNALISTA,
            'user[plainPassword][first]' => 'senha-segura-123',
            'user[plainPassword][second]' => 'senha-segura-123',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/admin/usuarios/');

        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'reporter@procordis.test']);
        self::assertNotNull($user);
        self::assertSame(User::ROLE_JORNALISTA, $user->getAccessType());
        self::assertContains('ROLE_JORNALISTA', $user->getRoles());
        self::assertNotContains('ROLE_ADMIN', $user->getRoles());
        self::assertNotSame('senha-segura-123', $user->getPassword(), 'A senha deve ser gravada com hash.');
    }

    public function testPasswordConfirmationMustMatch(): void
    {
        $this->loginAs(User::ROLE_ADMIN);
        $crawler = $this->client->request('GET', '/admin/usuarios/novo');

        $form = $crawler->selectButton('Criar usuário')->form([
            'user[email]' => 'outro@procordis.test',
            'user[accessType]' => User::ROLE_JORNALISTA,
            'user[plainPassword][first]' => 'senha-segura-123',
            'user[plainPassword][second]' => 'outra-senha-456',
        ]);
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form', 'As senhas informadas não conferem.');
    }

    public function testAdminCanPromoteJournalist(): void
    {
        $this->loginAs(User::ROLE_ADMIN);
        $journalist = $this->createUser(User::ROLE_JORNALISTA, 'promover@procordis.test');

        $crawler = $this->client->request('GET', '/admin/usuarios/'.$journalist->getId().'/editar');
        $form = $crawler->selectButton('Salvar alterações')->form(['user[accessType]' => User::ROLE_ADMIN]);
        $this->client->submit($form);

        self::assertResponseRedirects('/admin/usuarios/');
        $this->em()->clear();
        self::assertSame(User::ROLE_ADMIN, $this->em()->find(User::class, $journalist->getId())->getAccessType());
    }

    public function testAdminCannotDeleteHimself(): void
    {
        $admin = $this->loginAs(User::ROLE_ADMIN);
        $this->createUser(User::ROLE_ADMIN);

        $crawler = $this->client->request('GET', '/admin/usuarios/');
        self::assertCount(0, $crawler->filter(sprintf('form[action="/admin/usuarios/%d"]', $admin->getId())), 'O botão de excluir não aparece para o próprio usuário.');

        $this->client->request('POST', '/admin/usuarios/'.$admin->getId(), ['_token' => 'qualquer']);
        $this->client->followRedirect();

        $this->em()->clear();
        self::assertNotNull($this->em()->find(User::class, $admin->getId()));
    }

    public function testExistingAdminRolesAreUntouched(): void
    {
        // Administradores antigos têm roles = ["ROLE_ADMIN"] e continuam com acesso total.
        $legacyAdmin = (new User())->setEmail('legado@procordis.test')->setRoles(['ROLE_ADMIN'])->setPassword('x');
        $this->em()->persist($legacyAdmin);
        $this->em()->flush();

        self::assertSame(User::ROLE_ADMIN, $legacyAdmin->getAccessType());
        self::assertTrue($legacyAdmin->isAdmin());

        $this->client->loginUser($legacyAdmin);
        $this->client->request('GET', '/admin/general-data/edit');
        self::assertResponseIsSuccessful();
    }
}

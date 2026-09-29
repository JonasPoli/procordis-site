<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Já existe um usuário cadastrado com este e-mail.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /** Administrador geral: acesso total ao painel. */
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    /** Jornalista: apenas notícias, categorias de notícias e galeria. */
    public const ROLE_JORNALISTA = 'ROLE_JORNALISTA';

    /** Tipos de acesso que podem ser atribuídos pelo painel (rótulo => role). */
    public const ACCESS_TYPES = [
        'Administrador' => self::ROLE_ADMIN,
        'Jornalista' => self::ROLE_JORNALISTA,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'Informe o e-mail.')]
    #[Assert\Email(message: 'Informe um e-mail válido.')]
    #[Assert\Length(max: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Tipo de acesso principal do usuário no painel (ROLE_ADMIN ou ROLE_JORNALISTA).
     * Retorna null para usuários sem acesso ao painel.
     */
    public function getAccessType(): ?string
    {
        if (\in_array(self::ROLE_ADMIN, $this->roles, true)) {
            return self::ROLE_ADMIN;
        }

        if (\in_array(self::ROLE_JORNALISTA, $this->roles, true)) {
            return self::ROLE_JORNALISTA;
        }

        return null;
    }

    /**
     * Define o tipo de acesso, substituindo as roles de painel e preservando eventuais roles extras.
     */
    public function setAccessType(string $accessType): static
    {
        if (!\in_array($accessType, self::ACCESS_TYPES, true)) {
            throw new \InvalidArgumentException(sprintf('Tipo de acesso inválido: "%s".', $accessType));
        }

        $roles = array_values(array_diff($this->roles, self::ACCESS_TYPES));
        $roles[] = $accessType;
        $this->roles = $roles;

        return $this;
    }

    public function getAccessTypeLabel(): string
    {
        $label = array_search($this->getAccessType(), self::ACCESS_TYPES, true);

        return false === $label ? 'Sem acesso ao painel' : $label;
    }

    public function isAdmin(): bool
    {
        return self::ROLE_ADMIN === $this->getAccessType();
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }
}

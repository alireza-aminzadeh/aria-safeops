<?php

namespace App\Domain\Shared\Entity;

use App\Repository\UserRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'users_email_unique', columns: ['email'])]
#[ORM\UniqueConstraint(name: 'users_username_unique', columns: ['username'])]
#[ApiResource(
    operations: [new Get(), new GetCollection()],
    normalizationContext: ['groups' => ['user:read']],
    security: "is_granted('ROLE_USER') or is_granted('ROLE_PERMIT_ISSUER') or is_granted('ROLE_HSE_MANAGER') or is_granted('ROLE_ADMIN')",
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['user:read', 'permit:read', 'moc:read', 'incident:read'])]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Tenant $tenant;

    #[ORM\Column(length: 180)]
    #[Groups(['user:read', 'permit:read', 'moc:read', 'incident:read'])]
    private string $email;

    #[ORM\Column(length: 64)]
    #[Groups(['user:read', 'permit:read', 'moc:read', 'incident:read'])]
    private string $username;

    #[ORM\Column(name: 'password_hash')]
    private string $passwordHash;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    #[Groups(['user:read'])]
    private array $roles = [];

    #[ORM\Column(name: 'full_name', length: 180)]
    #[Groups(['user:read', 'permit:read', 'moc:read', 'incident:read'])]
    private string $fullName;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct(Tenant $tenant, string $email, string $fullName, array $roles, string $passwordHash, ?Uuid $id = null)
    {
        $this->id = $id ?? Uuid::v7();
        $this->tenant = $tenant;
        $this->email = strtolower($email);
        $this->username = strtolower(explode('@', $this->email)[0]);
        $this->fullName = $fullName;
        $this->roles = $roles;
        $this->passwordHash = $passwordHash;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTenant(): Tenant
    {
        return $this->tenant;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = strtolower($username);
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_values(array_unique($roles));
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function eraseCredentials(): void
    {
    }
}

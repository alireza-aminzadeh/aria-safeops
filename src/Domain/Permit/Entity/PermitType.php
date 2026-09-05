<?php

namespace App\Domain\Permit\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'permit_types')]
#[ApiResource(
    operations: [new Get(), new GetCollection()],
    normalizationContext: ['groups' => ['permit_type:read']],
    security: "is_granted('ROLE_USER')",
)]
class PermitType
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['permit_type:read', 'permit:read'])]
    private Uuid $id;

    #[ORM\Column(length: 64, unique: true)]
    #[Groups(['permit_type:read', 'permit:read'])]
    private string $code;

    #[ORM\Column(name: 'name_fa', length: 120)]
    #[Groups(['permit_type:read', 'permit:read'])]
    private string $nameFa;

    #[ORM\Column(name: 'name_en', length: 120)]
    #[Groups(['permit_type:read', 'permit:read'])]
    private string $nameEn;

    #[ORM\Column(name: 'requires_gas_test')]
    #[Groups(['permit_type:read', 'permit:read'])]
    private bool $requiresGasTest;

    #[ORM\Column(name: 'requires_isolation')]
    #[Groups(['permit_type:read', 'permit:read'])]
    private bool $requiresIsolation;

    public function __construct(string $code, string $nameFa, string $nameEn, bool $requiresGasTest, bool $requiresIsolation, ?Uuid $id = null)
    {
        $this->id = $id ?? Uuid::v7();
        $this->code = $code;
        $this->nameFa = $nameFa;
        $this->nameEn = $nameEn;
        $this->requiresGasTest = $requiresGasTest;
        $this->requiresIsolation = $requiresIsolation;
    }

    public function getId(): Uuid { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function getNameFa(): string { return $this->nameFa; }
    public function getNameEn(): string { return $this->nameEn; }
    public function requiresGasTest(): bool { return $this->requiresGasTest; }
    public function getRequiresGasTest(): bool { return $this->requiresGasTest; }
    public function requiresIsolation(): bool { return $this->requiresIsolation; }
    public function getRequiresIsolation(): bool { return $this->requiresIsolation; }
}

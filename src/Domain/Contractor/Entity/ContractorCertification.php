<?php

namespace App\Domain\Contractor\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'contractor_certifications')]
class ContractorCertification
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['contractor:read'])]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Contractor::class, inversedBy: 'certifications')]
    #[ORM\JoinColumn(nullable: false)]
    private Contractor $contractor;

    #[ORM\Column(length: 64)]
    #[Groups(['contractor:read'])]
    private string $type;

    #[ORM\Column(name: 'expires_at', type: 'date_immutable')]
    #[Groups(['contractor:read'])]
    private \DateTimeImmutable $expiresAt;

    public function __construct(Contractor $contractor, string $type, \DateTimeImmutable $expiresAt)
    {
        $this->id = Uuid::v7();
        $this->contractor = $contractor;
        $this->type = $type;
        $this->expiresAt = $expiresAt;
    }

    public function getId(): Uuid { return $this->id; }
    public function getContractor(): Contractor { return $this->contractor; }
    public function getType(): string { return $this->type; }
    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }
    #[Groups(['contractor:read'])]
    public function isExpiringSoon(): bool
    {
        return $this->expiresAt <= new \DateTimeImmutable('+30 days');
    }
}

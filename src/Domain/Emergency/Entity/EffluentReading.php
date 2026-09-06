<?php

namespace App\Domain\Emergency\Entity;

use App\Domain\Emergency\EffluentCompliancePolicy;
use App\Domain\Shared\Entity\Tenant;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'effluent_readings')]
class EffluentReading
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Tenant $tenant;

    #[ORM\Column(length: 64)]
    private string $parameter;

    #[ORM\Column(type: 'float')]
    private float $value;

    #[ORM\Column(length: 16)]
    private string $unit;

    #[ORM\Column(name: 'limit_value', type: 'float', nullable: true)]
    private ?float $limitValue;

    #[ORM\Column(length: 120)]
    private string $location;

    #[ORM\Column(type: 'boolean')]
    private bool $compliant;

    #[ORM\Column(name: 'sampled_at')]
    private \DateTimeImmutable $sampledAt;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Tenant $tenant,
        string $parameter,
        float $value,
        string $unit,
        ?float $limitValue,
        string $location,
        \DateTimeImmutable $sampledAt,
    ) {
        $this->id = Uuid::v7();
        $this->tenant = $tenant;
        $this->parameter = $parameter;
        $this->value = $value;
        $this->unit = $unit;
        $this->limitValue = $limitValue;
        $this->location = $location;
        $this->sampledAt = $sampledAt;
        $this->compliant = EffluentCompliancePolicy::isCompliant($parameter, $value, $limitValue);
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid { return $this->id; }
    public function getTenant(): Tenant { return $this->tenant; }
    public function getParameter(): string { return $this->parameter; }
    public function getValue(): float { return $this->value; }
    public function getUnit(): string { return $this->unit; }
    public function getLimitValue(): ?float { return $this->limitValue; }
    public function getLocation(): string { return $this->location; }
    public function isCompliant(): bool { return $this->compliant; }
    public function getSampledAt(): \DateTimeImmutable { return $this->sampledAt; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

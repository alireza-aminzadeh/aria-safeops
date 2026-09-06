<?php

namespace App\Domain\Emergency\Entity;

use App\Domain\Shared\Entity\Tenant;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'emergency_drills')]
class EmergencyDrill
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Tenant $tenant;

    #[ORM\ManyToOne(targetEntity: ErpPlan::class)]
    #[ORM\JoinColumn(name: 'erp_plan_id', nullable: true)]
    private ?ErpPlan $erpPlan = null;

    #[ORM\Column(length: 180)]
    private string $scenario;

    #[ORM\Column(name: 'held_at')]
    private \DateTimeImmutable $heldAt;

    #[ORM\Column(name: 'participant_count', type: 'integer')]
    private int $participantCount;

    #[ORM\Column(name: 'duration_minutes', type: 'integer')]
    private int $durationMinutes;

    #[ORM\Column(name: 'leader_name', length: 180)]
    private string $leaderName;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $findings;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Tenant $tenant,
        string $scenario,
        \DateTimeImmutable $heldAt,
        int $participantCount,
        int $durationMinutes,
        string $leaderName,
        ?string $findings = null,
        ?ErpPlan $erpPlan = null,
    ) {
        $this->id = Uuid::v7();
        $this->tenant = $tenant;
        $this->scenario = $scenario;
        $this->heldAt = $heldAt;
        $this->participantCount = $participantCount;
        $this->durationMinutes = $durationMinutes;
        $this->leaderName = $leaderName;
        $this->findings = $findings;
        $this->erpPlan = $erpPlan;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid { return $this->id; }
    public function getTenant(): Tenant { return $this->tenant; }
    public function getErpPlan(): ?ErpPlan { return $this->erpPlan; }
    public function getScenario(): string { return $this->scenario; }
    public function getHeldAt(): \DateTimeImmutable { return $this->heldAt; }
    public function getParticipantCount(): int { return $this->participantCount; }
    public function getDurationMinutes(): int { return $this->durationMinutes; }
    public function getLeaderName(): string { return $this->leaderName; }
    public function getFindings(): ?string { return $this->findings; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

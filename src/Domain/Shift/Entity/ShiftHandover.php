<?php

namespace App\Domain\Shift\Entity;

use App\Domain\Shared\Entity\Tenant;
use App\Domain\Shared\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'shift_handovers')]
class ShiftHandover
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Tenant $tenant;

    #[ORM\Column(name: 'shift_date', type: 'date_immutable')]
    private \DateTimeImmutable $shiftDate;

    #[ORM\Column(name: 'shift_name', length: 32)]
    private string $shiftName;

    #[ORM\Column(name: 'outgoing_name', length: 180)]
    private string $outgoingName;

    #[ORM\Column(name: 'incoming_name', length: 180)]
    private string $incomingName;

    #[ORM\Column(type: 'text')]
    private string $summary;

    #[ORM\Column(name: 'outstanding_work', type: 'text', nullable: true)]
    private ?string $outstandingWork;

    #[ORM\Column(length: 32)]
    private string $status = 'draft';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by', nullable: true)]
    private ?User $createdBy = null;

    #[ORM\Column(name: 'accepted_at', nullable: true)]
    private ?\DateTimeImmutable $acceptedAt = null;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Tenant $tenant,
        \DateTimeImmutable $shiftDate,
        string $shiftName,
        string $outgoingName,
        string $incomingName,
        string $summary,
        ?string $outstandingWork = null,
    ) {
        $this->id = Uuid::v7();
        $this->tenant = $tenant;
        $this->shiftDate = $shiftDate;
        $this->shiftName = $shiftName;
        $this->outgoingName = $outgoingName;
        $this->incomingName = $incomingName;
        $this->summary = $summary;
        $this->outstandingWork = $outstandingWork;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function submit(): void { $this->status = 'submitted'; }

    public function accept(): void
    {
        $this->status = 'accepted';
        $this->acceptedAt = new \DateTimeImmutable();
    }

    public function setCreatedBy(?User $user): void { $this->createdBy = $user; }

    public function getId(): Uuid { return $this->id; }
    public function getTenant(): Tenant { return $this->tenant; }
    public function getShiftDate(): \DateTimeImmutable { return $this->shiftDate; }
    public function getShiftName(): string { return $this->shiftName; }
    public function getOutgoingName(): string { return $this->outgoingName; }
    public function getIncomingName(): string { return $this->incomingName; }
    public function getSummary(): string { return $this->summary; }
    public function getOutstandingWork(): ?string { return $this->outstandingWork; }
    public function getStatus(): string { return $this->status; }
    public function getAcceptedAt(): ?\DateTimeImmutable { return $this->acceptedAt; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

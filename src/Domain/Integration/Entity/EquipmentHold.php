<?php

namespace App\Domain\Integration\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'petroops_equipment_holds')]
#[ORM\UniqueConstraint(name: 'uniq_petroops_holds_tag', columns: ['equipment_tag'])]
#[ORM\Index(name: 'idx_petroops_holds_status', columns: ['status'])]
class EquipmentHold
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(name: 'equipment_tag', length: 64)]
    private string $equipmentTag;

    #[ORM\Column(name: 'event_id', length: 64)]
    private string $eventId;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $score;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $summary;

    #[ORM\Column(length: 32)]
    private string $status;

    #[ORM\Column(name: 'detected_at')]
    private \DateTimeImmutable $detectedAt;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $equipmentTag,
        string $eventId,
        string $status,
        \DateTimeImmutable $detectedAt,
        ?float $score = null,
        ?string $summary = null,
    ) {
        $this->id = Uuid::v7();
        $this->equipmentTag = $equipmentTag;
        $this->eventId = $eventId;
        $this->status = $status;
        $this->detectedAt = $detectedAt;
        $this->score = $score;
        $this->summary = $summary;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function refresh(
        string $eventId,
        string $status,
        \DateTimeImmutable $detectedAt,
        ?float $score,
        ?string $summary,
    ): void {
        $this->eventId = $eventId;
        $this->status = $status;
        $this->detectedAt = $detectedAt;
        $this->score = $score;
        $this->summary = $summary;
    }

    public function getEquipmentTag(): string
    {
        return $this->equipmentTag;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }
}

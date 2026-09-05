<?php

namespace App\Domain\Shift\Entity;

use App\Domain\Shared\Entity\Tenant;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'toolbox_talks')]
class ToolboxTalk
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Tenant $tenant;

    #[ORM\Column(length: 180)]
    private string $topic;

    #[ORM\Column(length: 180)]
    private string $location;

    #[ORM\Column(name: 'held_at')]
    private \DateTimeImmutable $heldAt;

    #[ORM\Column(name: 'leader_name', length: 180)]
    private string $leaderName;

    #[ORM\Column(name: 'attendee_count', type: 'integer')]
    private int $attendeeCount;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes;

    public function __construct(
        Tenant $tenant,
        string $topic,
        string $location,
        \DateTimeImmutable $heldAt,
        string $leaderName,
        int $attendeeCount,
        ?string $notes = null,
    ) {
        $this->id = Uuid::v7();
        $this->tenant = $tenant;
        $this->topic = $topic;
        $this->location = $location;
        $this->heldAt = $heldAt;
        $this->leaderName = $leaderName;
        $this->attendeeCount = $attendeeCount;
        $this->notes = $notes;
    }

    public function getId(): Uuid { return $this->id; }
    public function getTenant(): Tenant { return $this->tenant; }
    public function getTopic(): string { return $this->topic; }
    public function getLocation(): string { return $this->location; }
    public function getHeldAt(): \DateTimeImmutable { return $this->heldAt; }
    public function getLeaderName(): string { return $this->leaderName; }
    public function getAttendeeCount(): int { return $this->attendeeCount; }
    public function getNotes(): ?string { return $this->notes; }
}

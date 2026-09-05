<?php

namespace App\Domain\Vision\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'vision_events')]
class VisionEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: VisionCamera::class)]
    #[ORM\JoinColumn(name: 'camera_id', nullable: false)]
    private VisionCamera $camera;

    #[ORM\Column(name: 'event_type', length: 32)]
    private string $eventType;

    #[ORM\Column(type: 'float')]
    private float $confidence;

    #[ORM\Column(type: 'text')]
    private string $summary;

    #[ORM\Column(length: 32)]
    private string $status = 'open';

    #[ORM\Column(name: 'detected_at')]
    private \DateTimeImmutable $detectedAt;

    public function __construct(VisionCamera $camera, string $eventType, float $confidence, string $summary)
    {
        $this->id = Uuid::v7();
        $this->camera = $camera;
        $this->eventType = $eventType;
        $this->confidence = $confidence;
        $this->summary = $summary;
        $this->detectedAt = new \DateTimeImmutable();
    }

    public function acknowledge(): void { $this->status = 'acknowledged'; }

    public function getId(): Uuid { return $this->id; }
    public function getCamera(): VisionCamera { return $this->camera; }
    public function getEventType(): string { return $this->eventType; }
    public function getConfidence(): float { return $this->confidence; }
    public function getSummary(): string { return $this->summary; }
    public function getStatus(): string { return $this->status; }
    public function getDetectedAt(): \DateTimeImmutable { return $this->detectedAt; }
}

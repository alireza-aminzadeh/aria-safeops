<?php

namespace App\Domain\Emergency\Entity;

use App\Domain\Shared\Entity\Tenant;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'erp_plans')]
class ErpPlan
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Tenant $tenant;

    #[ORM\Column(name: 'scenario_type', length: 32)]
    private string $scenarioType;

    #[ORM\Column(length: 180)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(name: 'reviewed_at', type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $reviewedAt;

    #[ORM\Column(name: 'next_review_due', type: 'date_immutable')]
    private \DateTimeImmutable $nextReviewDue;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Tenant $tenant,
        string $scenarioType,
        string $title,
        string $description,
        \DateTimeImmutable $nextReviewDue,
        ?\DateTimeImmutable $reviewedAt = null,
    ) {
        $this->id = Uuid::v7();
        $this->tenant = $tenant;
        $this->scenarioType = $scenarioType;
        $this->title = $title;
        $this->description = $description;
        $this->nextReviewDue = $nextReviewDue;
        $this->reviewedAt = $reviewedAt;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid { return $this->id; }
    public function getTenant(): Tenant { return $this->tenant; }
    public function getScenarioType(): string { return $this->scenarioType; }
    public function getTitle(): string { return $this->title; }
    public function getDescription(): string { return $this->description; }
    public function getReviewedAt(): ?\DateTimeImmutable { return $this->reviewedAt; }
    public function getNextReviewDue(): \DateTimeImmutable { return $this->nextReviewDue; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function isOverdue(): bool
    {
        return $this->nextReviewDue < new \DateTimeImmutable('today');
    }

    public function markReviewed(\DateTimeImmutable $nextReviewDue): void
    {
        $this->reviewedAt = new \DateTimeImmutable();
        $this->nextReviewDue = $nextReviewDue;
    }
}

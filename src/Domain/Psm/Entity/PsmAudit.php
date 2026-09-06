<?php

namespace App\Domain\Psm\Entity;

use App\Domain\Shared\Entity\Tenant;
use App\Domain\Shared\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * ممیزی PSM — چک‌لیست ۱۴ عنصر OSHA 1910.119. هر ممیزی هنگام ایجاد به‌صورت
 * خودکار با ۱۴ یافتهٔ خام (یک به‌ازای هر عنصر) پر می‌شود (بخش PsmAuditController).
 */
#[ORM\Entity]
#[ORM\Table(name: 'psm_audits')]
class PsmAudit
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Tenant $tenant;

    #[ORM\Column(length: 180)]
    private string $title;

    #[ORM\Column(name: 'audit_date', type: 'date_immutable')]
    private \DateTimeImmutable $auditDate;

    #[ORM\Column(name: 'auditor_name', length: 180)]
    private string $auditorName;

    #[ORM\Column(length: 32)]
    private string $status = 'draft';

    #[ORM\Column(name: 'overall_score_percent', type: 'float', nullable: true)]
    private ?float $overallScorePercent = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by', nullable: true)]
    private ?User $createdBy = null;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'completed_at', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    /** @var Collection<int, PsmAuditFinding> */
    #[ORM\OneToMany(targetEntity: PsmAuditFinding::class, mappedBy: 'audit', cascade: ['persist'])]
    private Collection $findings;

    public function __construct(Tenant $tenant, string $title, \DateTimeImmutable $auditDate, string $auditorName, ?User $createdBy = null)
    {
        $this->id = Uuid::v7();
        $this->tenant = $tenant;
        $this->title = $title;
        $this->auditDate = $auditDate;
        $this->auditorName = $auditorName;
        $this->createdBy = $createdBy;
        $this->createdAt = new \DateTimeImmutable();
        $this->findings = new ArrayCollection();
    }

    public function getId(): Uuid { return $this->id; }
    public function getTenant(): Tenant { return $this->tenant; }
    public function getTitle(): string { return $this->title; }
    public function getAuditDate(): \DateTimeImmutable { return $this->auditDate; }
    public function getAuditorName(): string { return $this->auditorName; }
    public function getStatus(): string { return $this->status; }
    public function getOverallScorePercent(): ?float { return $this->overallScorePercent; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getCompletedAt(): ?\DateTimeImmutable { return $this->completedAt; }

    public function addFinding(PsmAuditFinding $finding): void
    {
        $this->findings->add($finding);
    }

    /** @return Collection<int, PsmAuditFinding> */
    public function getFindings(): Collection { return $this->findings; }

    public function markInProgress(): void
    {
        if ($this->status === 'draft') {
            $this->status = 'in_progress';
        }
    }

    public function complete(float $overallScorePercent): void
    {
        $this->status = 'completed';
        $this->overallScorePercent = $overallScorePercent;
        $this->completedAt = new \DateTimeImmutable();
    }
}

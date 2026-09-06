<?php

namespace App\Domain\Incident\Entity;

use App\Domain\Shared\Entity\Tenant;
use App\Domain\Shared\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * ثبت دوره‌ای ساعت‌کار/تعداد نفرات — مخرج محاسبهٔ LTIFR/TRIR. بدون این عدد
 * واقعی، این دو شاخص فقط با یک تخمین ثابت (سرانه) قابل‌محاسبه‌اند.
 */
#[ORM\Entity]
#[ORM\Table(name: 'safety_period_metrics')]
class SafetyPeriodMetric
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Tenant $tenant;

    #[ORM\Column(name: 'period_start', type: 'date_immutable')]
    private \DateTimeImmutable $periodStart;

    #[ORM\Column(name: 'period_end', type: 'date_immutable')]
    private \DateTimeImmutable $periodEnd;

    #[ORM\Column(name: 'hours_worked', type: 'decimal', precision: 12, scale: 2)]
    private string $hoursWorked;

    #[ORM\Column(name: 'employee_count', type: 'integer', nullable: true)]
    private ?int $employeeCount;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by', nullable: true)]
    private ?User $createdBy;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Tenant $tenant,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
        string $hoursWorked,
        ?int $employeeCount = null,
        ?User $createdBy = null,
    ) {
        $this->id = Uuid::v7();
        $this->tenant = $tenant;
        $this->periodStart = $periodStart;
        $this->periodEnd = $periodEnd;
        $this->hoursWorked = $hoursWorked;
        $this->employeeCount = $employeeCount;
        $this->createdBy = $createdBy;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid { return $this->id; }
    public function getTenant(): Tenant { return $this->tenant; }
    public function getPeriodStart(): \DateTimeImmutable { return $this->periodStart; }
    public function getPeriodEnd(): \DateTimeImmutable { return $this->periodEnd; }
    public function getHoursWorked(): string { return $this->hoursWorked; }
    public function getEmployeeCount(): ?int { return $this->employeeCount; }
    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

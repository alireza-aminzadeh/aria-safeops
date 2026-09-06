<?php

namespace App\Domain\Incident\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Api\TenantAwarePersistProcessor;
use App\Domain\Shared\Entity\Tenant;
use App\Domain\Shared\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'incidents')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(processor: TenantAwarePersistProcessor::class),
        new Patch(),
    ],
    normalizationContext: ['groups' => ['incident:read']],
    denormalizationContext: ['groups' => ['incident:write']],
    security: "is_granted('ROLE_USER')",
    order: ['reportedAt' => 'DESC'],
)]
#[ApiFilter(SearchFilter::class, properties: ['status' => 'exact', 'type' => 'exact', 'severity' => 'exact'])]
class Incident
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['incident:read'])]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Tenant $tenant;

    #[ORM\Column(length: 32)]
    #[Groups(['incident:read', 'incident:write'])]
    #[Assert\Choice(choices: ['near_miss', 'incident', 'injury'])]
    private string $type = 'near_miss';

    #[ORM\Column(length: 32)]
    #[Groups(['incident:read', 'incident:write'])]
    #[Assert\Choice(choices: ['low', 'medium', 'high', 'critical'])]
    private string $severity = 'low';

    #[ORM\Column(length: 32)]
    #[Groups(['incident:read'])]
    private string $status = 'reported';

    #[ORM\Column(type: 'text')]
    #[Groups(['incident:read', 'incident:write'])]
    #[Assert\NotBlank]
    private string $description;

    #[ORM\Column(name: 'rca_notes', type: 'text', nullable: true)]
    #[Groups(['incident:read', 'incident:write'])]
    private ?string $rcaNotes = null;

    /**
     * تحلیل ریشه‌ای ساخت‌یافته (۵ چرا). هر عضو یک لایهٔ «چرا» را نگه می‌دارد؛
     * آخرین عضو غیرخالی معمولاً علت ریشه‌ای است.
     *
     * @var list<string>
     */
    #[ORM\Column(name: 'root_cause_whys', type: 'json')]
    #[Groups(['incident:read', 'incident:write'])]
    private array $rootCauseWhys = [];

    #[ORM\Column(name: 'root_cause_category', length: 32, nullable: true)]
    #[Groups(['incident:read', 'incident:write'])]
    #[Assert\Choice(choices: ['human_factor', 'procedure_gap', 'equipment_failure', 'design', 'training', 'management_system', 'external'], message: 'دستهٔ علت ریشه‌ای نامعتبر است.')]
    private ?string $rootCauseCategory = null;

    #[ORM\Column(name: 'lost_days', type: 'integer')]
    #[Groups(['incident:read', 'incident:write'])]
    #[Assert\PositiveOrZero]
    private int $lostDays = 0;

    /**
     * آیا این حادثه طبق قرارداد ثبت (OSHA 1904 / API 754) «ثبت‌شدنی» است —
     * مبنای محاسبهٔ TRIR. مقدار اولیه هنگام ایجاد به‌صورت خودکار از نوع/شدت
     * محاسبه می‌شود؛ مدیر HSE می‌تواند بعداً دستی بازنویسی کند.
     */
    #[ORM\Column(type: 'boolean')]
    #[Groups(['incident:read', 'incident:write'])]
    private bool $recordable = false;

    #[ORM\Column(length: 180, nullable: true)]
    #[Groups(['incident:read', 'incident:write'])]
    private ?string $location = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'reported_by', nullable: true)]
    #[Groups(['incident:read'])]
    private ?User $reportedBy = null;

    #[ORM\Column(name: 'reported_at')]
    #[Groups(['incident:read'])]
    private \DateTimeImmutable $reportedAt;

    /** @var Collection<int, CapaAction> */
    #[ORM\OneToMany(targetEntity: CapaAction::class, mappedBy: 'incident', cascade: ['persist'])]
    #[Groups(['incident:read'])]
    private Collection $capaActions;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->reportedAt = new \DateTimeImmutable();
        $this->capaActions = new ArrayCollection();
    }

    public function getId(): Uuid { return $this->id; }
    public function getTenant(): Tenant { return $this->tenant; }
    public function setTenant(Tenant $tenant): void { $this->tenant = $tenant; }
    public function getType(): string { return $this->type; }
    public function setType(string $type): void { $this->type = $type; }
    public function getSeverity(): string { return $this->severity; }
    public function setSeverity(string $severity): void { $this->severity = $severity; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function getRcaNotes(): ?string { return $this->rcaNotes; }
    public function setRcaNotes(?string $rcaNotes): void { $this->rcaNotes = $rcaNotes; }
    /** @return list<string> */
    public function getRootCauseWhys(): array { return $this->rootCauseWhys; }
    /** @param list<string> $whys */
    public function setRootCauseWhys(array $whys): void { $this->rootCauseWhys = array_values(array_map('strval', $whys)); }
    public function getRootCauseCategory(): ?string { return $this->rootCauseCategory; }
    public function setRootCauseCategory(?string $rootCauseCategory): void { $this->rootCauseCategory = $rootCauseCategory; }
    public function getLostDays(): int { return $this->lostDays; }
    public function setLostDays(int $lostDays): void { $this->lostDays = max(0, $lostDays); }
    public function isLostTimeInjury(): bool { return $this->lostDays > 0; }
    public function isRecordable(): bool { return $this->recordable; }
    public function setRecordable(bool $recordable): void { $this->recordable = $recordable; }
    public function getLocation(): ?string { return $this->location; }
    public function setLocation(?string $location): void { $this->location = $location; }
    public function getReportedBy(): ?User { return $this->reportedBy; }
    public function setReportedBy(?User $user): void { $this->reportedBy = $user; }
    public function getReportedAt(): \DateTimeImmutable { return $this->reportedAt; }
    public function addCapa(CapaAction $capa): void { $this->capaActions->add($capa); }
    /** @return Collection<int, CapaAction> */
    public function getCapaActions(): Collection { return $this->capaActions; }
}

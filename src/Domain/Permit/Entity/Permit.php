<?php

namespace App\Domain\Permit\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
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
#[ORM\Table(name: 'permits')]
#[ORM\Index(name: 'idx_permits_status', columns: ['status'])]
#[ORM\Index(name: 'idx_permits_equipment', columns: ['equipment_tag'])]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(
            processor: TenantAwarePersistProcessor::class,
            securityPostDenormalize: "is_granted('ROLE_PERMIT_ISSUER') or is_granted('ROLE_HSE_MANAGER') or is_granted('ROLE_ADMIN')",
        ),
        new Patch(security: "object.getStatus() == 'draft'"),
    ],
    normalizationContext: ['groups' => ['permit:read']],
    denormalizationContext: ['groups' => ['permit:write']],
    security: "is_granted('ROLE_USER')",
    order: ['createdAt' => 'DESC'],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'status' => 'exact',
    'equipmentTag' => 'partial',
    'locationPlotRef' => 'exact',
    'permitType.code' => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt', 'status', 'equipmentTag'])]
class Permit
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['permit:read'])]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Tenant $tenant;

    #[ORM\ManyToOne(targetEntity: PermitType::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['permit:read', 'permit:write'])]
    #[Assert\NotNull]
    private PermitType $permitType;

    #[ORM\Column(length: 32)]
    #[Groups(['permit:read'])]
    private string $status = 'draft';

    #[ORM\Column(name: 'equipment_tag', length: 64)]
    #[Groups(['permit:read', 'permit:write'])]
    #[Assert\NotBlank]
    private string $equipmentTag = '';

    #[ORM\Column(name: 'location_plot_ref', length: 120, nullable: true)]
    #[Groups(['permit:read', 'permit:write'])]
    private ?string $locationPlotRef = null;

    #[ORM\Column(name: 'work_description', type: 'text')]
    #[Groups(['permit:read', 'permit:write'])]
    private string $workDescription = '';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'requested_by', nullable: true)]
    #[Groups(['permit:read'])]
    private ?User $requestedBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'approved_by', nullable: true)]
    #[Groups(['permit:read'])]
    private ?User $approvedBy = null;

    #[ORM\Column(name: 'valid_from', nullable: true)]
    #[Groups(['permit:read', 'permit:write'])]
    private ?\DateTimeImmutable $validFrom = null;

    #[ORM\Column(name: 'valid_to', nullable: true)]
    #[Groups(['permit:read', 'permit:write'])]
    private ?\DateTimeImmutable $validTo = null;

    #[ORM\Column(name: 'jsa_reference', type: 'json')]
    #[Groups(['permit:read', 'permit:write'])]
    private array $jsaReference = [];

    #[ORM\Column(name: 'isolation_confirmed')]
    #[Groups(['permit:read'])]
    private bool $isolationConfirmed = false;

    /** @var list<string> */
    #[ORM\Column(name: 'isolation_points', type: 'json')]
    #[Groups(['permit:read'])]
    private array $isolationPoints = [];

    #[ORM\Column(name: 'isolation_confirmed_at', nullable: true)]
    #[Groups(['permit:read'])]
    private ?\DateTimeImmutable $isolationConfirmedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'isolation_confirmed_by', nullable: true)]
    #[Groups(['permit:read'])]
    private ?User $isolationConfirmedBy = null;

    #[ORM\Column(name: 'created_at')]
    #[Groups(['permit:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at')]
    #[Groups(['permit:read'])]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, GasTestReading> */
    #[ORM\OneToMany(targetEntity: GasTestReading::class, mappedBy: 'permit', cascade: ['persist'])]
    #[Groups(['permit:read'])]
    private Collection $gasTestReadings;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->gasTestReadings = new ArrayCollection();
    }

    public function getId(): Uuid { return $this->id; }
    public function getTenant(): Tenant { return $this->tenant; }
    public function setTenant(Tenant $tenant): void { $this->tenant = $tenant; }
    public function getPermitType(): PermitType { return $this->permitType; }
    public function setPermitType(PermitType $permitType): void { $this->permitType = $permitType; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; $this->updatedAt = new \DateTimeImmutable(); }
    public function getEquipmentTag(): string { return $this->equipmentTag; }
    public function setEquipmentTag(string $equipmentTag): void { $this->equipmentTag = $equipmentTag; }
    public function getLocationPlotRef(): ?string { return $this->locationPlotRef; }
    public function setLocationPlotRef(?string $locationPlotRef): void { $this->locationPlotRef = $locationPlotRef; }
    public function getWorkDescription(): string { return $this->workDescription; }
    public function setWorkDescription(string $workDescription): void { $this->workDescription = $workDescription; }
    public function getRequestedBy(): ?User { return $this->requestedBy; }
    public function setRequestedBy(?User $user): void { $this->requestedBy = $user; }
    public function getApprovedBy(): ?User { return $this->approvedBy; }
    public function setApprovedBy(?User $user): void { $this->approvedBy = $user; }
    public function getValidFrom(): ?\DateTimeImmutable { return $this->validFrom; }
    public function setValidFrom(?\DateTimeImmutable $validFrom): void { $this->validFrom = $validFrom; }
    public function getValidTo(): ?\DateTimeImmutable { return $this->validTo; }
    public function setValidTo(?\DateTimeImmutable $validTo): void { $this->validTo = $validTo; }
    public function getJsaReference(): array { return $this->jsaReference; }
    public function setJsaReference(array $jsaReference): void { $this->jsaReference = $jsaReference; }
    public function isIsolationConfirmed(): bool { return $this->isolationConfirmed; }
    /** @return list<string> */
    public function getIsolationPoints(): array { return $this->isolationPoints; }
    public function getIsolationConfirmedAt(): ?\DateTimeImmutable { return $this->isolationConfirmedAt; }
    public function getIsolationConfirmedBy(): ?User { return $this->isolationConfirmedBy; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    public function confirmIsolation(array $points, User $user): void
    {
        $this->isolationPoints = array_values(array_filter(array_map('strval', $points)));
        $this->isolationConfirmed = true;
        $this->isolationConfirmedAt = new \DateTimeImmutable();
        $this->isolationConfirmedBy = $user;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function addGasTestReading(GasTestReading $reading): void
    {
        $this->gasTestReadings->add($reading);
    }

    /** @return Collection<int, GasTestReading> */
    public function getGasTestReadings(): Collection { return $this->gasTestReadings; }
}

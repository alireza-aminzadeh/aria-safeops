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
    public function getLocation(): ?string { return $this->location; }
    public function setLocation(?string $location): void { $this->location = $location; }
    public function getReportedBy(): ?User { return $this->reportedBy; }
    public function setReportedBy(?User $user): void { $this->reportedBy = $user; }
    public function getReportedAt(): \DateTimeImmutable { return $this->reportedAt; }
    public function addCapa(CapaAction $capa): void { $this->capaActions->add($capa); }
    /** @return Collection<int, CapaAction> */
    public function getCapaActions(): Collection { return $this->capaActions; }
}

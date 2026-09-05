<?php

namespace App\Domain\Moc\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
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
#[ORM\Table(name: 'moc_requests')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(
            processor: TenantAwarePersistProcessor::class,
            securityPostDenormalize: "is_granted('ROLE_PERMIT_ISSUER') or is_granted('ROLE_HSE_MANAGER') or is_granted('ROLE_ADMIN')",
        ),
    ],
    normalizationContext: ['groups' => ['moc:read']],
    denormalizationContext: ['groups' => ['moc:write']],
    security: "is_granted('ROLE_USER')",
)]
#[ApiFilter(SearchFilter::class, properties: ['status' => 'exact', 'changeType' => 'exact', 'equipmentTag' => 'partial'])]
class MocRequest
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['moc:read'])]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Tenant $tenant;

    #[ORM\Column(length: 32)]
    #[Groups(['moc:read'])]
    private string $status = 'proposed';

    #[ORM\Column(name: 'change_type', length: 32)]
    #[Groups(['moc:read', 'moc:write'])]
    #[Assert\Choice(choices: ['temporary', 'permanent', 'emergency'])]
    private string $changeType = 'permanent';

    #[ORM\Column(type: 'text')]
    #[Groups(['moc:read', 'moc:write'])]
    #[Assert\NotBlank]
    private string $description;

    #[ORM\Column(name: 'equipment_tag', length: 64, nullable: true)]
    #[Groups(['moc:read', 'moc:write'])]
    private ?string $equipmentTag = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'requested_by', nullable: true)]
    #[Groups(['moc:read'])]
    private ?User $requestedBy = null;

    #[ORM\Column(name: 'pssr_completed_at', nullable: true)]
    #[Groups(['moc:read'])]
    private ?\DateTimeImmutable $pssrCompletedAt = null;

    /** @var Collection<int, HazopRegisterItem> */
    #[ORM\OneToMany(targetEntity: HazopRegisterItem::class, mappedBy: 'mocRequest', cascade: ['persist'])]
    #[Groups(['moc:read'])]
    private Collection $hazopItems;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->hazopItems = new ArrayCollection();
    }

    public function getId(): Uuid { return $this->id; }
    public function getTenant(): Tenant { return $this->tenant; }
    public function setTenant(Tenant $tenant): void { $this->tenant = $tenant; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void
    {
        $this->status = $status;
        if ($status === 'pssr') {
            $this->pssrCompletedAt = new \DateTimeImmutable();
        }
    }
    public function getChangeType(): string { return $this->changeType; }
    public function setChangeType(string $changeType): void { $this->changeType = $changeType; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function getEquipmentTag(): ?string { return $this->equipmentTag; }
    public function setEquipmentTag(?string $equipmentTag): void { $this->equipmentTag = $equipmentTag; }
    public function getRequestedBy(): ?User { return $this->requestedBy; }
    public function setRequestedBy(?User $user): void { $this->requestedBy = $user; }
    public function getPssrCompletedAt(): ?\DateTimeImmutable { return $this->pssrCompletedAt; }
}

<?php

namespace App\Domain\Moc\Entity;

use App\Domain\Psm\Entity\BowtieBarrier;
use App\Domain\Psm\Entity\LopaScenario;
use App\Domain\Shared\Entity\Tenant;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'hazop_register_items')]
class HazopRegisterItem
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['moc:read', 'hazop:read'])]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(name: 'tenant_id', nullable: true)]
    private ?Tenant $tenant = null;

    #[ORM\ManyToOne(targetEntity: MocRequest::class, inversedBy: 'hazopItems')]
    #[ORM\JoinColumn(name: 'moc_request_id', nullable: true)]
    private ?MocRequest $mocRequest = null;

    #[ORM\Column(name: 'equipment_tag', length: 64, nullable: true)]
    #[Groups(['hazop:read'])]
    private ?string $equipmentTag = null;

    #[ORM\Column(name: 'node_description', type: 'text')]
    #[Groups(['moc:read', 'hazop:read'])]
    private string $nodeDescription;

    #[ORM\Column(length: 120)]
    #[Groups(['moc:read', 'hazop:read'])]
    private string $deviation;

    #[ORM\Column(type: 'text')]
    #[Groups(['moc:read', 'hazop:read'])]
    private string $cause;

    #[ORM\Column(type: 'text')]
    #[Groups(['moc:read', 'hazop:read'])]
    private string $consequence;

    #[ORM\Column(type: 'text')]
    #[Groups(['moc:read', 'hazop:read'])]
    private string $safeguards;

    #[ORM\Column(name: 'risk_ranking', length: 32)]
    #[Groups(['moc:read', 'hazop:read'])]
    private string $riskRanking;

    #[ORM\Column(length: 32)]
    #[Groups(['hazop:read'])]
    private string $status = 'open';

    /** @var Collection<int, LopaScenario> */
    #[ORM\OneToMany(targetEntity: LopaScenario::class, mappedBy: 'hazopItem', cascade: ['persist'])]
    private Collection $lopaScenarios;

    /** @var Collection<int, BowtieBarrier> */
    #[ORM\OneToMany(targetEntity: BowtieBarrier::class, mappedBy: 'hazopItem', cascade: ['persist'])]
    private Collection $barriers;

    public function __construct(
        string $nodeDescription,
        string $deviation,
        string $cause,
        string $consequence,
        string $safeguards,
        string $riskRanking,
    ) {
        $this->id = Uuid::v7();
        $this->nodeDescription = $nodeDescription;
        $this->deviation = $deviation;
        $this->cause = $cause;
        $this->consequence = $consequence;
        $this->safeguards = $safeguards;
        $this->riskRanking = $riskRanking;
        $this->lopaScenarios = new ArrayCollection();
        $this->barriers = new ArrayCollection();
    }

    public function getId(): Uuid { return $this->id; }
    public function getTenant(): ?Tenant { return $this->tenant; }
    public function setTenant(?Tenant $tenant): void { $this->tenant = $tenant; }
    public function getMocRequest(): ?MocRequest { return $this->mocRequest; }
    public function setMocRequest(?MocRequest $mocRequest): void { $this->mocRequest = $mocRequest; }
    public function getEquipmentTag(): ?string { return $this->equipmentTag; }
    public function setEquipmentTag(?string $equipmentTag): void { $this->equipmentTag = $equipmentTag; }
    public function getNodeDescription(): string { return $this->nodeDescription; }
    public function getDeviation(): string { return $this->deviation; }
    public function getCause(): string { return $this->cause; }
    public function getConsequence(): string { return $this->consequence; }
    public function getSafeguards(): string { return $this->safeguards; }
    public function getRiskRanking(): string { return $this->riskRanking; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }

    public function addLopa(LopaScenario $scenario): void
    {
        $this->lopaScenarios->add($scenario);
    }

    public function addBarrier(BowtieBarrier $barrier): void
    {
        $this->barriers->add($barrier);
    }

    /** @return Collection<int, LopaScenario> */
    public function getLopaScenarios(): Collection { return $this->lopaScenarios; }
    /** @return Collection<int, BowtieBarrier> */
    public function getBarriers(): Collection { return $this->barriers; }
}

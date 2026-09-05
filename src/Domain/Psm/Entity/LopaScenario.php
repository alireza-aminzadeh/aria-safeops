<?php

namespace App\Domain\Psm\Entity;

use App\Domain\Moc\Entity\HazopRegisterItem;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'lopa_scenarios')]
class LopaScenario
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: HazopRegisterItem::class, inversedBy: 'lopaScenarios')]
    #[ORM\JoinColumn(name: 'hazop_item_id', nullable: false)]
    private HazopRegisterItem $hazopItem;

    #[ORM\Column(name: 'initiating_event', type: 'text')]
    private string $initiatingEvent;

    #[ORM\Column(name: 'ipl_count', type: 'integer')]
    private int $iplCount;

    #[ORM\Column(name: 'target_frequency', length: 64)]
    private string $targetFrequency;

    #[ORM\Column(name: 'residual_risk', length: 32)]
    private string $residualRisk;

    public function __construct(
        HazopRegisterItem $hazopItem,
        string $initiatingEvent,
        int $iplCount,
        string $targetFrequency,
        string $residualRisk,
    ) {
        $this->id = Uuid::v7();
        $this->hazopItem = $hazopItem;
        $this->initiatingEvent = $initiatingEvent;
        $this->iplCount = $iplCount;
        $this->targetFrequency = $targetFrequency;
        $this->residualRisk = $residualRisk;
    }

    public function getId(): Uuid { return $this->id; }
    public function getHazopItem(): HazopRegisterItem { return $this->hazopItem; }
    public function getInitiatingEvent(): string { return $this->initiatingEvent; }
    public function getIplCount(): int { return $this->iplCount; }
    public function getTargetFrequency(): string { return $this->targetFrequency; }
    public function getResidualRisk(): string { return $this->residualRisk; }
}

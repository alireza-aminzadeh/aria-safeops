<?php

namespace App\Domain\Psm\Entity;

use App\Domain\Moc\Entity\HazopRegisterItem;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'bowtie_barriers')]
class BowtieBarrier
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: HazopRegisterItem::class, inversedBy: 'barriers')]
    #[ORM\JoinColumn(name: 'hazop_item_id', nullable: false)]
    private HazopRegisterItem $hazopItem;

    #[ORM\Column(length: 32)]
    private string $side;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(length: 32)]
    private string $effectiveness;

    #[ORM\Column(length: 32)]
    private string $status;

    public function __construct(
        HazopRegisterItem $hazopItem,
        string $side,
        string $description,
        string $effectiveness,
        string $status = 'in_place',
    ) {
        $this->id = Uuid::v7();
        $this->hazopItem = $hazopItem;
        $this->side = $side;
        $this->description = $description;
        $this->effectiveness = $effectiveness;
        $this->status = $status;
    }

    public function getId(): Uuid { return $this->id; }
    public function getHazopItem(): HazopRegisterItem { return $this->hazopItem; }
    public function getSide(): string { return $this->side; }
    public function getDescription(): string { return $this->description; }
    public function getEffectiveness(): string { return $this->effectiveness; }
    public function getStatus(): string { return $this->status; }
}

<?php

namespace App\Domain\Moc\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'hazop_register_items')]
class HazopRegisterItem
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['moc:read'])]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: MocRequest::class, inversedBy: 'hazopItems')]
    #[ORM\JoinColumn(name: 'moc_request_id', nullable: true)]
    private ?MocRequest $mocRequest = null;

    #[ORM\Column(name: 'node_description', type: 'text')]
    #[Groups(['moc:read'])]
    private string $nodeDescription;

    #[ORM\Column(length: 120)]
    #[Groups(['moc:read'])]
    private string $deviation;

    #[ORM\Column(type: 'text')]
    #[Groups(['moc:read'])]
    private string $cause;

    #[ORM\Column(type: 'text')]
    #[Groups(['moc:read'])]
    private string $consequence;

    #[ORM\Column(type: 'text')]
    #[Groups(['moc:read'])]
    private string $safeguards;

    #[ORM\Column(name: 'risk_ranking', length: 32)]
    #[Groups(['moc:read'])]
    private string $riskRanking;

    public function __construct(string $nodeDescription, string $deviation, string $cause, string $consequence, string $safeguards, string $riskRanking)
    {
        $this->id = Uuid::v7();
        $this->nodeDescription = $nodeDescription;
        $this->deviation = $deviation;
        $this->cause = $cause;
        $this->consequence = $consequence;
        $this->safeguards = $safeguards;
        $this->riskRanking = $riskRanking;
    }
}

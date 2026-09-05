<?php

namespace App\Domain\Incident\Entity;

use App\Domain\Shared\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'capa_actions')]
class CapaAction
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['incident:read'])]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Incident::class, inversedBy: 'capaActions')]
    #[ORM\JoinColumn(nullable: false)]
    private Incident $incident;

    #[ORM\Column(type: 'text')]
    #[Groups(['incident:read'])]
    private string $description;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'assigned_to', nullable: true)]
    #[Groups(['incident:read'])]
    private ?User $assignedTo = null;

    #[ORM\Column(name: 'due_date', type: 'date_immutable', nullable: true)]
    #[Groups(['incident:read'])]
    private ?\DateTimeImmutable $dueDate = null;

    #[ORM\Column(length: 32)]
    #[Groups(['incident:read'])]
    private string $status = 'open';

    public function __construct(Incident $incident, string $description, ?\DateTimeImmutable $dueDate = null)
    {
        $this->id = Uuid::v7();
        $this->incident = $incident;
        $this->description = $description;
        $this->dueDate = $dueDate;
    }

    public function getId(): Uuid { return $this->id; }
    public function getIncident(): Incident { return $this->incident; }
    public function getDescription(): string { return $this->description; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function getDueDate(): ?\DateTimeImmutable { return $this->dueDate; }
    public function getAssignedTo(): ?User { return $this->assignedTo; }
}

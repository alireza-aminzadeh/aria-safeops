<?php

namespace App\Domain\Psm\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'psm_audit_findings')]
class PsmAuditFinding
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: PsmAudit::class, inversedBy: 'findings')]
    #[ORM\JoinColumn(nullable: false)]
    private PsmAudit $audit;

    #[ORM\Column(name: 'element_code', length: 64)]
    private string $elementCode;

    #[ORM\Column(name: 'element_name_fa', length: 180)]
    private string $elementNameFa;

    #[ORM\Column(length: 32)]
    private string $rating = 'not_applicable';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'corrective_action', type: 'text', nullable: true)]
    private ?string $correctiveAction = null;

    #[ORM\Column(name: 'due_date', type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $dueDate = null;

    #[ORM\Column(length: 32)]
    private string $status = 'open';

    public function __construct(PsmAudit $audit, string $elementCode, string $elementNameFa)
    {
        $this->id = Uuid::v7();
        $this->audit = $audit;
        $this->elementCode = $elementCode;
        $this->elementNameFa = $elementNameFa;
    }

    public function getId(): Uuid { return $this->id; }
    public function getAudit(): PsmAudit { return $this->audit; }
    public function getElementCode(): string { return $this->elementCode; }
    public function getElementNameFa(): string { return $this->elementNameFa; }
    public function getRating(): string { return $this->rating; }
    public function getNotes(): ?string { return $this->notes; }
    public function getCorrectiveAction(): ?string { return $this->correctiveAction; }
    public function getDueDate(): ?\DateTimeImmutable { return $this->dueDate; }
    public function getStatus(): string { return $this->status; }

    public function update(string $rating, ?string $notes, ?string $correctiveAction, ?\DateTimeImmutable $dueDate): void
    {
        $this->rating = $rating;
        $this->notes = $notes;
        $this->correctiveAction = $correctiveAction;
        $this->dueDate = $dueDate;
        if ($rating === 'non_compliant' || $rating === 'partial') {
            $this->status = 'open';
        } elseif ($correctiveAction === null || trim($correctiveAction) === '') {
            $this->status = 'closed';
        }
    }

    public function closeAction(): void
    {
        $this->status = 'closed';
    }
}

<?php

namespace App\Domain\Contractor\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'contractor_training_records')]
class ContractorTrainingRecord
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['contractor:read'])]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Contractor::class, inversedBy: 'trainingRecords')]
    #[ORM\JoinColumn(nullable: false)]
    private Contractor $contractor;

    #[ORM\Column(name: 'course_code', length: 64)]
    #[Groups(['contractor:read'])]
    private string $courseCode;

    #[ORM\Column(name: 'course_name', length: 180)]
    #[Groups(['contractor:read'])]
    private string $courseName;

    #[ORM\Column(name: 'completed_at', type: 'date_immutable')]
    #[Groups(['contractor:read'])]
    private \DateTimeImmutable $completedAt;

    #[ORM\Column(name: 'expires_at', type: 'date_immutable', nullable: true)]
    #[Groups(['contractor:read'])]
    private ?\DateTimeImmutable $expiresAt;

    public function __construct(
        Contractor $contractor,
        string $courseCode,
        string $courseName,
        \DateTimeImmutable $completedAt,
        ?\DateTimeImmutable $expiresAt = null,
    ) {
        $this->id = Uuid::v7();
        $this->contractor = $contractor;
        $this->courseCode = $courseCode;
        $this->courseName = $courseName;
        $this->completedAt = $completedAt;
        $this->expiresAt = $expiresAt;
    }

    public function getId(): Uuid { return $this->id; }
    public function getContractor(): Contractor { return $this->contractor; }
    public function getCourseCode(): string { return $this->courseCode; }
    public function getCourseName(): string { return $this->courseName; }
    public function getCompletedAt(): \DateTimeImmutable { return $this->completedAt; }
    public function getExpiresAt(): ?\DateTimeImmutable { return $this->expiresAt; }

    #[Groups(['contractor:read'])]
    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt < new \DateTimeImmutable('today');
    }
}

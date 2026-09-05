<?php

namespace App\Domain\Permit\Entity;

use App\Domain\Shared\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'gas_test_readings')]
class GasTestReading
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['permit:read'])]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Permit::class, inversedBy: 'gasTestReadings')]
    #[ORM\JoinColumn(nullable: false)]
    private Permit $permit;

    #[ORM\Column(name: 'gas_type', length: 16)]
    #[Groups(['permit:read'])]
    private string $gasType;

    #[ORM\Column(name: 'reading_value', type: 'decimal', precision: 10, scale: 3)]
    #[Groups(['permit:read'])]
    private string $readingValue;

    #[ORM\Column(name: 'recorded_at')]
    #[Groups(['permit:read'])]
    private \DateTimeImmutable $recordedAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'recorded_by', nullable: true)]
    private ?User $recordedBy = null;

    public function __construct(Permit $permit, string $gasType, string $readingValue, ?User $recordedBy = null)
    {
        $this->id = Uuid::v7();
        $this->permit = $permit;
        $this->gasType = $gasType;
        $this->readingValue = $readingValue;
        $this->recordedBy = $recordedBy;
        $this->recordedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid { return $this->id; }
    public function getGasType(): string { return $this->gasType; }
    public function getReadingValue(): string { return $this->readingValue; }
    public function getRecordedAt(): \DateTimeImmutable { return $this->recordedAt; }
}

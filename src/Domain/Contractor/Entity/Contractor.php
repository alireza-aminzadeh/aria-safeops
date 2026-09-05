<?php

namespace App\Domain\Contractor\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'contractors')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(securityPostDenormalize: "is_granted('ROLE_HSE_MANAGER') or is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['contractor:read']],
    denormalizationContext: ['groups' => ['contractor:write']],
    security: "is_granted('ROLE_USER')",
)]
class Contractor
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['contractor:read'])]
    private Uuid $id;

    #[ORM\Column(name: 'company_name', length: 180)]
    #[Groups(['contractor:read', 'contractor:write'])]
    #[Assert\NotBlank]
    private string $companyName;

    #[ORM\Column(name: 'hse_prequalification_score', type: 'decimal', precision: 5, scale: 2, nullable: true)]
    #[Groups(['contractor:read', 'contractor:write'])]
    private ?string $hsePrequalificationScore = null;

    /** @var Collection<int, ContractorCertification> */
    #[ORM\OneToMany(targetEntity: ContractorCertification::class, mappedBy: 'contractor', cascade: ['persist'])]
    #[Groups(['contractor:read'])]
    private Collection $certifications;

    /** @var Collection<int, ContractorTrainingRecord> */
    #[ORM\OneToMany(targetEntity: ContractorTrainingRecord::class, mappedBy: 'contractor', cascade: ['persist'])]
    #[Groups(['contractor:read'])]
    private Collection $trainingRecords;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->certifications = new ArrayCollection();
        $this->trainingRecords = new ArrayCollection();
    }

    public function getId(): Uuid { return $this->id; }
    public function getCompanyName(): string { return $this->companyName; }
    public function setCompanyName(string $companyName): void { $this->companyName = $companyName; }
    public function getHsePrequalificationScore(): ?string { return $this->hsePrequalificationScore; }
    public function setHsePrequalificationScore(?string $hsePrequalificationScore): void { $this->hsePrequalificationScore = $hsePrequalificationScore; }
    public function addCertification(ContractorCertification $certification): void { $this->certifications->add($certification); }
    public function addTraining(ContractorTrainingRecord $record): void { $this->trainingRecords->add($record); }
    /** @return Collection<int, ContractorCertification> */
    public function getCertifications(): Collection { return $this->certifications; }
    /** @return Collection<int, ContractorTrainingRecord> */
    public function getTrainingRecords(): Collection { return $this->trainingRecords; }
}

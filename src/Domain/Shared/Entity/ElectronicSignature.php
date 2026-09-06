<?php

namespace App\Domain\Shared\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * امضای الکترونیک روی تصمیمات حساس گردش‌کار (تأیید/فعال‌سازی مجوز، تأیید MOC).
 * جدا از زنجیرهٔ ممیزی عمومی (audit_log) نگه‌داشته می‌شود چون معنای حقوقی
 * متفاوتی دارد: «من، به نام [signerName]، این تصمیم را تأیید می‌کنم».
 */
#[ORM\Entity]
#[ORM\Table(name: 'electronic_signatures')]
#[ORM\Index(name: 'idx_esign_entity', columns: ['entity_type', 'entity_id'])]
class ElectronicSignature
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(name: 'tenant_id', type: 'uuid')]
    private Uuid $tenantId;

    #[ORM\Column(name: 'entity_type', length: 32)]
    private string $entityType;

    #[ORM\Column(name: 'entity_id', length: 64)]
    private string $entityId;

    #[ORM\Column(length: 64)]
    private string $action;

    #[ORM\Column(name: 'signer_id', type: 'uuid', nullable: true)]
    private ?Uuid $signerId;

    #[ORM\Column(name: 'signer_name', length: 180)]
    private string $signerName;

    #[ORM\Column(name: 'signed_at')]
    private \DateTimeImmutable $signedAt;

    #[ORM\Column(name: 'content_hash', length: 64)]
    private string $contentHash;

    public function __construct(
        Uuid $tenantId,
        string $entityType,
        string $entityId,
        string $action,
        ?Uuid $signerId,
        string $signerName,
        \DateTimeImmutable $signedAt,
        string $contentHash,
    ) {
        $this->id = Uuid::v7();
        $this->tenantId = $tenantId;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->action = $action;
        $this->signerId = $signerId;
        $this->signerName = $signerName;
        $this->signedAt = $signedAt;
        $this->contentHash = $contentHash;
    }

    public function getId(): Uuid { return $this->id; }
    public function getTenantId(): Uuid { return $this->tenantId; }
    public function getEntityType(): string { return $this->entityType; }
    public function getEntityId(): string { return $this->entityId; }
    public function getAction(): string { return $this->action; }
    public function getSignerId(): ?Uuid { return $this->signerId; }
    public function getSignerName(): string { return $this->signerName; }
    public function getSignedAt(): \DateTimeImmutable { return $this->signedAt; }
    public function getContentHash(): string { return $this->contentHash; }
}

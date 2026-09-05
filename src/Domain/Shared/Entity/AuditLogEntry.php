<?php

namespace App\Domain\Shared\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'audit_log')]
class AuditLogEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?string $id = null;

    #[ORM\Column(name: 'tenant_id', type: 'uuid')]
    private Uuid $tenantId;

    #[ORM\Column(length: 64)]
    private string $entity;

    #[ORM\Column(name: 'entity_id', length: 64)]
    private string $entityId;

    #[ORM\Column(length: 64)]
    private string $action;

    #[ORM\Column(name: 'actor_id', type: 'uuid', nullable: true)]
    private ?Uuid $actorId = null;

    #[ORM\Column(type: 'json')]
    private array $payload = [];

    #[ORM\Column(name: 'prev_hash', length: 64)]
    private string $prevHash;

    #[ORM\Column(length: 64)]
    private string $hash;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Uuid $tenantId,
        string $entity,
        string $entityId,
        string $action,
        string $prevHash,
        string $hash,
        ?Uuid $actorId = null,
        array $payload = [],
    ) {
        $this->tenantId = $tenantId;
        $this->entity = $entity;
        $this->entityId = $entityId;
        $this->action = $action;
        $this->prevHash = $prevHash;
        $this->hash = $hash;
        $this->actorId = $actorId;
        $this->payload = $payload;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getPrevHash(): string
    {
        return $this->prevHash;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getTenantId(): Uuid { return $this->tenantId; }
    public function getEntity(): string { return $this->entity; }
    public function getEntityId(): string { return $this->entityId; }
    public function getAction(): string { return $this->action; }
    public function getActorId(): ?Uuid { return $this->actorId; }
    public function getPayload(): array { return $this->payload; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

<?php

namespace App\Domain\Shared\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'ai_query_log')]
class AiQueryLog
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(name: 'tenant_id', type: 'uuid')]
    private Uuid $tenantId;

    #[ORM\Column(name: 'user_id', type: 'uuid', nullable: true)]
    private ?Uuid $userId;

    #[ORM\Column(name: 'query_text', type: 'text')]
    private string $queryText;

    #[ORM\Column(name: 'response_text', type: 'text', nullable: true)]
    private ?string $responseText = null;

    #[ORM\Column(length: 32)]
    private string $status = 'unavailable';

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct(Uuid $tenantId, string $queryText, ?Uuid $userId = null)
    {
        $this->id = Uuid::v7();
        $this->tenantId = $tenantId;
        $this->queryText = $queryText;
        $this->userId = $userId;
        $this->createdAt = new \DateTimeImmutable();
    }
}

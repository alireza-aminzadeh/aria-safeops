<?php

namespace App\Domain\Shift\Entity;

use App\Domain\Shared\Entity\Tenant;
use App\Domain\Shared\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'logbook_entries')]
class LogbookEntry
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Tenant $tenant;

    #[ORM\Column(length: 32)]
    private string $category;

    #[ORM\Column(type: 'text')]
    private string $body;

    #[ORM\Column(name: 'author_name', length: 180)]
    private string $authorName;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'author_id', nullable: true)]
    private ?User $author = null;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct(Tenant $tenant, string $category, string $body, string $authorName, ?User $author = null)
    {
        $this->id = Uuid::v7();
        $this->tenant = $tenant;
        $this->category = $category;
        $this->body = $body;
        $this->authorName = $authorName;
        $this->author = $author;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid { return $this->id; }
    public function getTenant(): Tenant { return $this->tenant; }
    public function getCategory(): string { return $this->category; }
    public function getBody(): string { return $this->body; }
    public function getAuthorName(): string { return $this->authorName; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}

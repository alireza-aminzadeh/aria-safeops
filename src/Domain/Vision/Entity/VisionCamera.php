<?php

namespace App\Domain\Vision\Entity;

use App\Domain\Shared\Entity\Tenant;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'vision_cameras')]
class VisionCamera
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Tenant $tenant;

    #[ORM\Column(length: 120)]
    private string $name;

    #[ORM\Column(length: 120)]
    private string $area;

    #[ORM\Column(name: 'rtsp_url', length: 255, nullable: true)]
    private ?string $rtspUrl;

    #[ORM\Column(type: 'boolean')]
    private bool $enabled = true;

    public function __construct(Tenant $tenant, string $name, string $area, ?string $rtspUrl = null)
    {
        $this->id = Uuid::v7();
        $this->tenant = $tenant;
        $this->name = $name;
        $this->area = $area;
        $this->rtspUrl = $rtspUrl;
    }

    public function getId(): Uuid { return $this->id; }
    public function getTenant(): Tenant { return $this->tenant; }
    public function getName(): string { return $this->name; }
    public function getArea(): string { return $this->area; }
    public function getRtspUrl(): ?string { return $this->rtspUrl; }
    public function isEnabled(): bool { return $this->enabled; }
}

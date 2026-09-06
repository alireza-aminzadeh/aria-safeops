<?php

namespace App\Infrastructure\Audit;

use App\Domain\Shared\ElectronicSignatureHasher;
use App\Domain\Shared\Entity\AuditLogEntry;
use App\Domain\Shared\Entity\ElectronicSignature;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class ElectronicSignatureService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function sign(Uuid $tenantId, string $entityType, string $entityId, string $action, ?Uuid $signerId, string $signerName): ElectronicSignature
    {
        $lastAudit = $this->em->getRepository(AuditLogEntry::class)->findOneBy(
            ['tenantId' => $tenantId],
            ['id' => 'DESC'],
        );
        $linkedAuditHash = $lastAudit?->getHash() ?? str_repeat('0', 64);
        $signedAt = new \DateTimeImmutable();
        $hash = ElectronicSignatureHasher::hash(
            $entityType,
            $entityId,
            $action,
            $signerId ? (string) $signerId : null,
            $signerName,
            $signedAt->format(\DateTimeInterface::ATOM),
            $linkedAuditHash,
        );

        $signature = new ElectronicSignature($tenantId, $entityType, $entityId, $action, $signerId, $signerName, $signedAt, $hash);
        $this->em->persist($signature);
        $this->em->flush();

        return $signature;
    }
}

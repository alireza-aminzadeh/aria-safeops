<?php

namespace App\Infrastructure\Audit;

use App\Domain\Shared\Entity\AuditLogEntry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final class AuditLogger
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function record(Uuid $tenantId, string $entity, string $entityId, string $action, ?Uuid $actorId = null, array $payload = []): void
    {
        $last = $this->em->getRepository(AuditLogEntry::class)->findOneBy(
            ['tenantId' => $tenantId],
            ['id' => 'DESC'],
        );

        $prevHash = $last?->getHash() ?? str_repeat('0', 64);
        $timestamp = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
        $material = implode('|', [
            $prevHash,
            (string) $tenantId,
            $entity,
            $entityId,
            $action,
            $actorId ? (string) $actorId : '',
            json_encode($payload, JSON_UNESCAPED_UNICODE),
            $timestamp,
        ]);
        $hash = hash('sha256', $material);

        $this->em->persist(new AuditLogEntry($tenantId, $entity, $entityId, $action, $prevHash, $hash, $actorId, $payload));
        $this->em->flush();
    }

    public function verifyChain(Uuid $tenantId): array
    {
        $rows = $this->em->getRepository(AuditLogEntry::class)->findBy(
            ['tenantId' => $tenantId],
            ['id' => 'ASC'],
        );
        $prev = str_repeat('0', 64);
        foreach ($rows as $row) {
            if ($row->getPrevHash() !== $prev) {
                return ['valid' => false, 'brokenAt' => $row->getId()];
            }
            $prev = $row->getHash();
        }
        return ['valid' => true];
    }
}

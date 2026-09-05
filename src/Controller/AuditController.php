<?php

namespace App\Controller;

use App\Domain\Shared\Entity\AuditLogEntry;
use App\Domain\Shared\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class AuditController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/api/audit', methods: ['GET'])]
    public function __invoke(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $qb = $this->em->createQueryBuilder()
            ->select('a')
            ->from(AuditLogEntry::class, 'a')
            ->where('a.tenantId = :tenant')
            ->setParameter('tenant', $user->getTenant()->getId())
            ->orderBy('a.id', 'DESC')
            ->setMaxResults(50);

        $entity = (string) $request->query->get('entity', '');
        $entityId = (string) $request->query->get('entityId', '');
        if ($entity !== '') {
            $qb->andWhere('a.entity = :entity')->setParameter('entity', $entity);
        }
        if ($entityId !== '') {
            $qb->andWhere('a.entityId = :entityId')->setParameter('entityId', $entityId);
        }

        /** @var list<AuditLogEntry> $rows */
        $rows = $qb->getQuery()->getResult();
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => $row->getId(),
                'entity' => $row->getEntity(),
                'entityId' => $row->getEntityId(),
                'action' => $row->getAction(),
                'actorId' => $row->getActorId() ? (string) $row->getActorId() : null,
                'payload' => $row->getPayload(),
                'hash' => $row->getHash(),
                'createdAt' => $row->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        return $this->json(['member' => $items]);
    }
}

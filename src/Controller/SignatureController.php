<?php

namespace App\Controller;

use App\Domain\Shared\Entity\ElectronicSignature;
use App\Domain\Shared\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class SignatureController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/api/signatures', methods: ['GET'])]
    public function __invoke(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from(ElectronicSignature::class, 's')
            ->where('s.tenantId = :tenant')
            ->setParameter('tenant', $user->getTenant()->getId())
            ->orderBy('s.signedAt', 'DESC')
            ->setMaxResults(50);

        $entityType = (string) $request->query->get('entityType', '');
        $entityId = (string) $request->query->get('entityId', '');
        if ($entityType !== '') {
            $qb->andWhere('s.entityType = :entityType')->setParameter('entityType', $entityType);
        }
        if ($entityId !== '') {
            $qb->andWhere('s.entityId = :entityId')->setParameter('entityId', $entityId);
        }

        /** @var list<ElectronicSignature> $rows */
        $rows = $qb->getQuery()->getResult();
        $items = array_map(static fn (ElectronicSignature $row) => [
            'id' => (string) $row->getId(),
            'entityType' => $row->getEntityType(),
            'entityId' => $row->getEntityId(),
            'action' => $row->getAction(),
            'signerName' => $row->getSignerName(),
            'signedAt' => $row->getSignedAt()->format(\DateTimeInterface::ATOM),
            'contentHash' => $row->getContentHash(),
        ], $rows);

        return $this->json(['member' => $items]);
    }
}

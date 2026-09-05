<?php

namespace App\Domain\Permit\Workflow;

use App\Domain\Permit\Entity\Permit;
use Doctrine\ORM\EntityManagerInterface;

final class PermitConflictChecker
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function hasActiveConflict(Permit $permit): bool
    {
        $location = trim((string) $permit->getLocationPlotRef());

        $qb = $this->em->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(Permit::class, 'p')
            ->where('p.status = :status')
            ->andWhere('p.id != :id')
            ->setParameter('status', 'active')
            ->setParameter('id', $permit->getId());

        if ($location !== '') {
            $qb->andWhere('p.equipmentTag = :tag OR p.locationPlotRef = :loc')
                ->setParameter('tag', $permit->getEquipmentTag())
                ->setParameter('loc', $location);
        } else {
            $qb->andWhere('p.equipmentTag = :tag')
                ->setParameter('tag', $permit->getEquipmentTag());
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}

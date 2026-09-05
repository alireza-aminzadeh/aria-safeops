<?php

namespace App\Controller;

use App\Domain\Contractor\Entity\ContractorCertification;
use App\Domain\Incident\Entity\Incident;
use App\Domain\Moc\Entity\HazopRegisterItem;
use App\Domain\Psm\Api754KpiCalculator;
use App\Domain\Shared\Entity\User;
use App\Domain\Shift\Entity\ToolboxTalk;
use App\Domain\Vision\Entity\VisionEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class Api754Controller extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/api/kpis/api754', methods: ['GET'])]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $tenant = $user->getTenant();
        /** @var list<Incident> $incidents */
        $incidents = $this->em->getRepository(Incident::class)->findBy(['tenant' => $tenant]);
        $rows = array_map(static fn (Incident $i) => [
            'type' => $i->getType(),
            'severity' => $i->getSeverity(),
        ], $incidents);
        $counts = Api754KpiCalculator::counts($rows);

        $openHighHazop = (int) $this->em->createQueryBuilder()
            ->select('COUNT(h.id)')
            ->from(HazopRegisterItem::class, 'h')
            ->andWhere('h.tenant = :tenant')
            ->andWhere('h.status = :open')
            ->andWhere('h.riskRanking IN (:high)')
            ->setParameter('tenant', $tenant)
            ->setParameter('open', 'open')
            ->setParameter('high', ['high', 'critical'])
            ->getQuery()
            ->getSingleScalarResult();

        $expiredCerts = (int) $this->em->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(ContractorCertification::class, 'c')
            ->andWhere('c.expiresAt < :today')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->getQuery()
            ->getSingleScalarResult();

        $talksThisMonth = (int) $this->em->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(ToolboxTalk::class, 't')
            ->andWhere('t.tenant = :tenant')
            ->andWhere('t.heldAt >= :from')
            ->setParameter('tenant', $tenant)
            ->setParameter('from', new \DateTimeImmutable('first day of this month midnight'))
            ->getQuery()
            ->getSingleScalarResult();

        $openVision = (int) $this->em->createQueryBuilder()
            ->select('COUNT(v.id)')
            ->from(VisionEvent::class, 'v')
            ->join('v.camera', 'cam')
            ->andWhere('cam.tenant = :tenant')
            ->andWhere('v.status = :open')
            ->setParameter('tenant', $tenant)
            ->setParameter('open', 'open')
            ->getQuery()
            ->getSingleScalarResult();

        $hours = max(1.0, 8760 / 12);
        $pseRate = round(($counts['tier1'] + $counts['tier2']) / $hours * 1_000_000, 3);

        return $this->json([
            'tier1' => $counts['tier1'],
            'tier2' => $counts['tier2'],
            'tier3' => $counts['tier3'],
            'tier4' => $counts['tier4'],
            'pseRatePerMillionHours' => $pseRate,
            'leading' => [
                'openHighHazop' => $openHighHazop,
                'expiredCertifications' => $expiredCerts,
                'toolboxTalksThisMonth' => $talksThisMonth,
                'openVisionEvents' => $openVision,
            ],
        ]);
    }
}

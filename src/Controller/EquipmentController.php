<?php

namespace App\Controller;

use App\Domain\Incident\Entity\Incident;
use App\Domain\Integration\Entity\EquipmentHold;
use App\Domain\Moc\Entity\MocRequest;
use App\Domain\Permit\Entity\Permit;
use App\Domain\Shared\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * وضعیت لحظه‌ای یک تجهیز — برای صفحهٔ عمومی (داخلی) QR روی تجهیز: آیا نگه‌داشت
 * پتروپایش دارد؟ چه مجوز/MOC باز روی آن جاری است؟ آخرین حوادث مرتبط با ناحیه.
 */
final class EquipmentController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/api/equipment/{tag}/status', methods: ['GET'])]
    public function status(string $tag, #[CurrentUser] User $user): JsonResponse
    {
        $tenant = $user->getTenant();

        $hold = $this->em->getRepository(EquipmentHold::class)->findOneBy(['equipmentTag' => $tag]);

        /** @var list<Permit> $permits */
        $permits = $this->em->createQueryBuilder()
            ->select('p')
            ->from(Permit::class, 'p')
            ->andWhere('p.tenant = :tenant')
            ->andWhere('p.equipmentTag = :tag')
            ->andWhere('p.status NOT IN (:closedStates)')
            ->setParameter('tenant', $tenant)
            ->setParameter('tag', $tag)
            ->setParameter('closedStates', ['closed', 'cancelled', 'rejected'])
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        /** @var list<MocRequest> $mocs */
        $mocs = $this->em->createQueryBuilder()
            ->select('m')
            ->from(MocRequest::class, 'm')
            ->andWhere('m.tenant = :tenant')
            ->andWhere('m.equipmentTag = :tag')
            ->andWhere('m.status NOT IN (:closedStates)')
            ->setParameter('tenant', $tenant)
            ->setParameter('tag', $tag)
            ->setParameter('closedStates', ['closed', 'rejected'])
            ->orderBy('m.id', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        /** @var list<Incident> $incidents */
        $incidents = $this->em->createQueryBuilder()
            ->select('i')
            ->from(Incident::class, 'i')
            ->andWhere('i.tenant = :tenant')
            ->andWhere('i.location LIKE :tag')
            ->setParameter('tenant', $tenant)
            ->setParameter('tag', '%' . $tag . '%')
            ->orderBy('i.reportedAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $this->json([
            'equipmentTag' => $tag,
            'hold' => $hold instanceof EquipmentHold ? [
                'status' => $hold->getStatus(),
                'blocked' => $hold->getStatus() === 'open',
                'score' => $hold->getScore(),
                'summary' => $hold->getSummary(),
            ] : ['status' => 'none', 'blocked' => false],
            'permits' => array_map(static fn (Permit $p) => [
                'id' => (string) $p->getId(),
                'status' => $p->getStatus(),
                'permitTypeCode' => $p->getPermitType()->getCode(),
                'validFrom' => $p->getValidFrom()?->format(\DateTimeInterface::ATOM),
                'validTo' => $p->getValidTo()?->format(\DateTimeInterface::ATOM),
            ], $permits),
            'mocs' => array_map(static fn (MocRequest $m) => [
                'id' => (string) $m->getId(),
                'status' => $m->getStatus(),
                'changeType' => $m->getChangeType(),
                'description' => $m->getDescription(),
            ], $mocs),
            'recentIncidents' => array_map(static fn (Incident $i) => [
                'id' => (string) $i->getId(),
                'type' => $i->getType(),
                'severity' => $i->getSeverity(),
                'status' => $i->getStatus(),
                'reportedAt' => $i->getReportedAt()->format(\DateTimeInterface::ATOM),
            ], $incidents),
        ]);
    }
}

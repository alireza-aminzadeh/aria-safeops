<?php

namespace App\Controller;

use App\Domain\Emergency\EffluentCompliancePolicy;
use App\Domain\Emergency\Entity\EffluentReading;
use App\Domain\Emergency\Entity\EmergencyDrill;
use App\Domain\Emergency\Entity\ErpPlan;
use App\Domain\Shared\Entity\User;
use App\Infrastructure\Mercure\MercurePublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

final class EmergencyController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MercurePublisher $mercure,
    ) {
    }

    // ---------------------------------------------------------------- ERP plans

    #[Route('/api/erp-plans', methods: ['GET'])]
    public function plans(#[CurrentUser] User $user): JsonResponse
    {
        $items = $this->em->getRepository(ErpPlan::class)->findBy(['tenant' => $user->getTenant()], ['nextReviewDue' => 'ASC']);

        return $this->json(['items' => array_map($this->plan(...), $items)]);
    }

    #[Route('/api/erp-plans', methods: ['POST'])]
    public function createPlan(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_HSE_MANAGER');
        $payload = json_decode($request->getContent(), true) ?? [];
        $title = trim((string) ($payload['title'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        if ($title === '' || $description === '') {
            throw new UnprocessableEntityHttpException('عنوان و شرح طرح الزامی است.');
        }
        $scenarioType = (string) ($payload['scenarioType'] ?? 'fire');
        if (!in_array($scenarioType, ['fire', 'gas_release', 'spill', 'medical', 'security', 'natural_disaster'], true)) {
            throw new UnprocessableEntityHttpException('نوع سناریو نامعتبر است.');
        }
        $plan = new ErpPlan(
            $user->getTenant(),
            $scenarioType,
            $title,
            $description,
            new \DateTimeImmutable((string) ($payload['nextReviewDue'] ?? '+365 days')),
        );
        $this->em->persist($plan);
        $this->em->flush();

        return $this->json($this->plan($plan), 201);
    }

    #[Route('/api/erp-plans/{id}/reviewed', methods: ['POST'])]
    public function reviewPlan(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_HSE_MANAGER');
        $plan = $this->em->find(ErpPlan::class, Uuid::fromString($id));
        if (!$plan || (string) $plan->getTenant()->getId() !== (string) $user->getTenant()->getId()) {
            throw $this->createNotFoundException();
        }
        $payload = json_decode($request->getContent(), true) ?? [];
        $plan->markReviewed(new \DateTimeImmutable((string) ($payload['nextReviewDue'] ?? '+365 days')));
        $this->em->flush();

        return $this->json($this->plan($plan));
    }

    // ---------------------------------------------------------------- Drills

    #[Route('/api/emergency-drills', methods: ['GET'])]
    public function drills(#[CurrentUser] User $user): JsonResponse
    {
        $items = $this->em->getRepository(EmergencyDrill::class)->findBy(['tenant' => $user->getTenant()], ['heldAt' => 'DESC'], 50);

        return $this->json(['items' => array_map($this->drill(...), $items)]);
    }

    #[Route('/api/emergency-drills', methods: ['POST'])]
    public function createDrill(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $scenario = trim((string) ($payload['scenario'] ?? ''));
        if ($scenario === '') {
            throw new UnprocessableEntityHttpException('سناریوی مانور الزامی است.');
        }
        $erpPlan = null;
        if (!empty($payload['erpPlanId'])) {
            $erpPlan = $this->em->find(ErpPlan::class, Uuid::fromString((string) $payload['erpPlanId']));
        }
        $drill = new EmergencyDrill(
            $user->getTenant(),
            $scenario,
            new \DateTimeImmutable((string) ($payload['heldAt'] ?? 'now')),
            (int) ($payload['participantCount'] ?? 0),
            (int) ($payload['durationMinutes'] ?? 0),
            (string) ($payload['leaderName'] ?? $user->getFullName()),
            isset($payload['findings']) ? (string) $payload['findings'] : null,
            $erpPlan,
        );
        $this->em->persist($drill);
        $this->em->flush();
        $this->mercure->publish('emergency', ['type' => 'drill.created', 'id' => (string) $drill->getId()]);

        return $this->json($this->drill($drill), 201);
    }

    // ---------------------------------------------------------------- Effluent / environment

    #[Route('/api/effluent-readings', methods: ['GET'])]
    public function readings(#[CurrentUser] User $user): JsonResponse
    {
        $items = $this->em->getRepository(EffluentReading::class)->findBy(['tenant' => $user->getTenant()], ['sampledAt' => 'DESC'], 100);

        return $this->json(['items' => array_map($this->reading(...), $items)]);
    }

    #[Route('/api/effluent-readings', methods: ['POST'])]
    public function createReading(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $parameter = trim((string) ($payload['parameter'] ?? ''));
        if ($parameter === '' || !isset($payload['value'])) {
            throw new UnprocessableEntityHttpException('پارامتر و مقدار اندازه‌گیری‌شده الزامی است.');
        }
        $limit = isset($payload['limitValue']) && $payload['limitValue'] !== ''
            ? (float) $payload['limitValue']
            : (EffluentCompliancePolicy::referenceLimits()[$parameter] ?? null);

        $reading = new EffluentReading(
            $user->getTenant(),
            $parameter,
            (float) $payload['value'],
            (string) ($payload['unit'] ?? 'mg/L'),
            $limit,
            (string) ($payload['location'] ?? 'خروجی پساب'),
            new \DateTimeImmutable((string) ($payload['sampledAt'] ?? 'now')),
        );
        $this->em->persist($reading);
        $this->em->flush();
        if (!$reading->isCompliant()) {
            $this->mercure->publish('emergency', [
                'type' => 'effluent.non_compliant',
                'parameter' => $reading->getParameter(),
                'value' => $reading->getValue(),
                'limit' => $reading->getLimitValue(),
            ]);
        }

        return $this->json($this->reading($reading), 201);
    }

    #[Route('/api/emergency/overview', methods: ['GET'])]
    public function overview(#[CurrentUser] User $user): JsonResponse
    {
        $tenant = $user->getTenant();
        $plans = $this->em->getRepository(ErpPlan::class)->findBy(['tenant' => $tenant]);
        $overduePlans = count(array_filter($plans, static fn (ErpPlan $p) => $p->isOverdue()));

        $sinceOneYear = new \DateTimeImmutable('-365 days');
        $drillsLastYear = (int) $this->em->createQueryBuilder()
            ->select('COUNT(d.id)')
            ->from(EmergencyDrill::class, 'd')
            ->andWhere('d.tenant = :tenant')
            ->andWhere('d.heldAt >= :since')
            ->setParameter('tenant', $tenant)
            ->setParameter('since', $sinceOneYear)
            ->getQuery()
            ->getSingleScalarResult();

        $nonCompliantReadings = (int) $this->em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(EffluentReading::class, 'r')
            ->andWhere('r.tenant = :tenant')
            ->andWhere('r.compliant = false')
            ->andWhere('r.sampledAt >= :since')
            ->setParameter('tenant', $tenant)
            ->setParameter('since', $sinceOneYear)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->json([
            'totalPlans' => count($plans),
            'overduePlans' => $overduePlans,
            'drillsLastYear' => $drillsLastYear,
            'nonCompliantReadingsLastYear' => $nonCompliantReadings,
        ]);
    }

    /** @return array<string, mixed> */
    private function plan(ErpPlan $plan): array
    {
        return [
            'id' => (string) $plan->getId(),
            'scenarioType' => $plan->getScenarioType(),
            'title' => $plan->getTitle(),
            'description' => $plan->getDescription(),
            'reviewedAt' => $plan->getReviewedAt()?->format('Y-m-d'),
            'nextReviewDue' => $plan->getNextReviewDue()->format('Y-m-d'),
            'overdue' => $plan->isOverdue(),
        ];
    }

    /** @return array<string, mixed> */
    private function drill(EmergencyDrill $drill): array
    {
        return [
            'id' => (string) $drill->getId(),
            'erpPlanId' => $drill->getErpPlan()?->getId() ? (string) $drill->getErpPlan()->getId() : null,
            'scenario' => $drill->getScenario(),
            'heldAt' => $drill->getHeldAt()->format(DATE_ATOM),
            'participantCount' => $drill->getParticipantCount(),
            'durationMinutes' => $drill->getDurationMinutes(),
            'leaderName' => $drill->getLeaderName(),
            'findings' => $drill->getFindings(),
        ];
    }

    /** @return array<string, mixed> */
    private function reading(EffluentReading $reading): array
    {
        return [
            'id' => (string) $reading->getId(),
            'parameter' => $reading->getParameter(),
            'value' => $reading->getValue(),
            'unit' => $reading->getUnit(),
            'limitValue' => $reading->getLimitValue(),
            'location' => $reading->getLocation(),
            'compliant' => $reading->isCompliant(),
            'sampledAt' => $reading->getSampledAt()->format(DATE_ATOM),
        ];
    }
}

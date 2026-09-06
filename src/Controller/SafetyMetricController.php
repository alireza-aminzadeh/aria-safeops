<?php

namespace App\Controller;

use App\Domain\Incident\Entity\SafetyPeriodMetric;
use App\Domain\Shared\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class SafetyMetricController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/api/safety-period-metrics', methods: ['GET'])]
    public function list(#[CurrentUser] User $user): JsonResponse
    {
        $items = $this->em->getRepository(SafetyPeriodMetric::class)->findBy(
            ['tenant' => $user->getTenant()],
            ['periodStart' => 'DESC'],
            24,
        );

        return $this->json(['items' => array_map($this->serialize(...), $items)]);
    }

    #[Route('/api/safety-period-metrics', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_HSE_MANAGER');
        $payload = json_decode($request->getContent(), true) ?? [];
        $start = trim((string) ($payload['periodStart'] ?? ''));
        $end = trim((string) ($payload['periodEnd'] ?? ''));
        $hours = (float) ($payload['hoursWorked'] ?? 0);
        if ($start === '' || $end === '') {
            throw new UnprocessableEntityHttpException('بازهٔ زمانی (شروع و پایان دوره) الزامی است.');
        }
        if ($hours <= 0) {
            throw new UnprocessableEntityHttpException('ساعت‌کار دوره باید عددی مثبت باشد.');
        }
        $periodStart = new \DateTimeImmutable($start);
        $periodEnd = new \DateTimeImmutable($end);
        if ($periodEnd < $periodStart) {
            throw new UnprocessableEntityHttpException('پایان دوره نمی‌تواند قبل از شروع آن باشد.');
        }

        $row = new SafetyPeriodMetric(
            $user->getTenant(),
            $periodStart,
            $periodEnd,
            number_format($hours, 2, '.', ''),
            isset($payload['employeeCount']) && $payload['employeeCount'] !== '' ? (int) $payload['employeeCount'] : null,
            $user,
        );
        $this->em->persist($row);
        $this->em->flush();

        return $this->json($this->serialize($row), 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(SafetyPeriodMetric $row): array
    {
        return [
            'id' => (string) $row->getId(),
            'periodStart' => $row->getPeriodStart()->format('Y-m-d'),
            'periodEnd' => $row->getPeriodEnd()->format('Y-m-d'),
            'hoursWorked' => $row->getHoursWorked(),
            'employeeCount' => $row->getEmployeeCount(),
            'createdAt' => $row->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}

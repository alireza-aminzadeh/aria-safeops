<?php

namespace App\Controller;

use App\Domain\Psm\Entity\PsmAudit;
use App\Domain\Psm\Entity\PsmAuditFinding;
use App\Domain\Psm\PsmAuditScoreCalculator;
use App\Domain\Psm\PsmElements;
use App\Domain\Shared\Entity\User;
use App\Infrastructure\Audit\AuditLogger;
use App\Infrastructure\Mercure\MercurePublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

final class PsmAuditController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AuditLogger $audit,
        private readonly MercurePublisher $mercure,
    ) {
    }

    #[Route('/api/psm-audits', methods: ['GET'])]
    public function list(#[CurrentUser] User $user): JsonResponse
    {
        $items = $this->em->getRepository(PsmAudit::class)->findBy(
            ['tenant' => $user->getTenant()],
            ['auditDate' => 'DESC'],
        );

        return $this->json(['items' => array_map($this->summarize(...), $items)]);
    }

    #[Route('/api/psm-audits', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_HSE_MANAGER');
        $payload = json_decode($request->getContent(), true) ?? [];
        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            throw new UnprocessableEntityHttpException('عنوان ممیزی الزامی است.');
        }
        $audit = new PsmAudit(
            $user->getTenant(),
            $title,
            new \DateTimeImmutable((string) ($payload['auditDate'] ?? 'today')),
            (string) ($payload['auditorName'] ?? $user->getFullName()),
            $user,
        );
        foreach (PsmElements::all() as $element) {
            $audit->addFinding(new PsmAuditFinding($audit, $element['code'], $element['nameFa']));
        }
        $this->em->persist($audit);
        $this->em->flush();
        $this->audit->record($user->getTenant()->getId(), 'psm_audit', (string) $audit->getId(), 'created', $user->getId());

        return $this->json($this->detail($audit), 201);
    }

    #[Route('/api/psm-audits/{id}', methods: ['GET'])]
    public function detailAction(string $id, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->detail($this->find($id, $user)));
    }

    #[Route('/api/psm-audits/{id}/findings/{findingId}', methods: ['PATCH'])]
    public function updateFinding(string $id, string $findingId, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_PERMIT_ISSUER');
        $auditEntity = $this->find($id, $user);
        $finding = $this->em->find(PsmAuditFinding::class, Uuid::fromString($findingId));
        if (!$finding || (string) $finding->getAudit()->getId() !== (string) $auditEntity->getId()) {
            throw $this->createNotFoundException();
        }
        if ($auditEntity->getStatus() === 'completed') {
            throw new UnprocessableEntityHttpException('ممیزی تکمیل‌شده قابل ویرایش نیست.');
        }
        $payload = json_decode($request->getContent(), true) ?? [];
        $rating = (string) ($payload['rating'] ?? $finding->getRating());
        if (!in_array($rating, ['compliant', 'partial', 'non_compliant', 'not_applicable'], true)) {
            throw new UnprocessableEntityHttpException('ارزیابی نامعتبر است.');
        }
        $due = isset($payload['dueDate']) && $payload['dueDate'] ? new \DateTimeImmutable((string) $payload['dueDate']) : null;
        $finding->update(
            $rating,
            isset($payload['notes']) ? (string) $payload['notes'] : null,
            isset($payload['correctiveAction']) ? (string) $payload['correctiveAction'] : null,
            $due,
        );
        $auditEntity->markInProgress();
        $this->em->flush();

        return $this->json($this->detail($auditEntity));
    }

    #[Route('/api/psm-audits/{id}/findings/{findingId}/close-action', methods: ['POST'])]
    public function closeAction(string $id, string $findingId, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_PERMIT_ISSUER');
        $auditEntity = $this->find($id, $user);
        $finding = $this->em->find(PsmAuditFinding::class, Uuid::fromString($findingId));
        if (!$finding || (string) $finding->getAudit()->getId() !== (string) $auditEntity->getId()) {
            throw $this->createNotFoundException();
        }
        $finding->closeAction();
        $this->em->flush();

        return $this->json($this->detail($auditEntity));
    }

    #[Route('/api/psm-audits/{id}/complete', methods: ['POST'])]
    public function complete(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_HSE_MANAGER');
        $auditEntity = $this->find($id, $user);
        $ratings = $auditEntity->getFindings()->map(static fn (PsmAuditFinding $f) => $f->getRating())->toArray();
        $score = PsmAuditScoreCalculator::overallPercent($ratings);
        if ($score === null) {
            throw new UnprocessableEntityHttpException('حداقل یک عنصر باید ارزیابی شود (غیر از «قابل‌اجرا نیست»).');
        }
        $auditEntity->complete($score);
        $this->em->flush();
        $this->audit->record($user->getTenant()->getId(), 'psm_audit', (string) $auditEntity->getId(), 'completed', $user->getId(), ['score' => $score]);
        $this->mercure->publish('psm', ['type' => 'psm_audit.completed', 'id' => (string) $auditEntity->getId(), 'score' => $score]);

        return $this->json($this->detail($auditEntity));
    }

    private function find(string $id, User $user): PsmAudit
    {
        $audit = $this->em->find(PsmAudit::class, Uuid::fromString($id));
        if (!$audit || (string) $audit->getTenant()->getId() !== (string) $user->getTenant()->getId()) {
            throw $this->createNotFoundException();
        }

        return $audit;
    }

    /** @return array<string, mixed> */
    private function summarize(PsmAudit $audit): array
    {
        return [
            'id' => (string) $audit->getId(),
            'title' => $audit->getTitle(),
            'auditDate' => $audit->getAuditDate()->format('Y-m-d'),
            'auditorName' => $audit->getAuditorName(),
            'status' => $audit->getStatus(),
            'overallScorePercent' => $audit->getOverallScorePercent(),
            'openFindings' => $audit->getFindings()->filter(static fn (PsmAuditFinding $f) => $f->getStatus() === 'open')->count(),
        ];
    }

    /** @return array<string, mixed> */
    private function detail(PsmAudit $audit): array
    {
        return $this->summarize($audit) + [
            'findings' => $audit->getFindings()->map(static fn (PsmAuditFinding $f) => [
                'id' => (string) $f->getId(),
                'elementCode' => $f->getElementCode(),
                'elementNameFa' => $f->getElementNameFa(),
                'rating' => $f->getRating(),
                'notes' => $f->getNotes(),
                'correctiveAction' => $f->getCorrectiveAction(),
                'dueDate' => $f->getDueDate()?->format('Y-m-d'),
                'status' => $f->getStatus(),
            ])->toArray(),
        ];
    }
}

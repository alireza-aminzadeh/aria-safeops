<?php

namespace App\Controller;

use App\Domain\Incident\Entity\CapaAction;
use App\Domain\Incident\Entity\Incident;
use App\Domain\Incident\IncidentStatusMachine;
use App\Domain\Shared\Entity\User;
use App\Infrastructure\Audit\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

final class CapaController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AuditLogger $audit,
    ) {
    }

    #[Route('/api/incidents/{id}/capa-actions', methods: ['POST'])]
    public function create(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $incident = $this->em->find(Incident::class, Uuid::fromString($id));
        if (!$incident) {
            throw $this->createNotFoundException();
        }
        $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $description = trim((string) ($payload['description'] ?? ''));
        if ($description === '') {
            throw new UnprocessableEntityHttpException('شرح اقدام اصلاحی الزامی است.');
        }

        $due = isset($payload['dueDate']) && $payload['dueDate']
            ? new \DateTimeImmutable((string) $payload['dueDate'])
            : null;

        $capa = new CapaAction($incident, $description, $due);
        $incident->addCapa($capa);
        $incident->setStatus(IncidentStatusMachine::afterCapaAdded($incident->getStatus()));
        $this->em->persist($capa);
        $this->audit->record(
            $incident->getTenant()->getId(),
            'incident',
            (string) $incident->getId(),
            'capa_added',
            $user->getId(),
            ['capaId' => (string) $capa->getId()],
        );
        $this->em->flush();

        return $this->json([
            'id' => (string) $capa->getId(),
            'status' => $capa->getStatus(),
            'incidentStatus' => $incident->getStatus(),
        ], 201);
    }

    #[Route('/api/incidents/{id}/capa-actions/{capaId}', methods: ['PATCH'])]
    public function update(string $id, string $capaId, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $incident = $this->em->find(Incident::class, Uuid::fromString($id));
        if (!$incident) {
            throw $this->createNotFoundException();
        }
        $capa = $this->em->find(CapaAction::class, Uuid::fromString($capaId));
        if (!$capa || (string) $capa->getIncident()->getId() !== (string) $incident->getId()) {
            throw $this->createNotFoundException();
        }

        $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $status = (string) ($payload['status'] ?? '');
        if (!in_array($status, ['open', 'in_progress', 'verified', 'closed'], true)) {
            throw new UnprocessableEntityHttpException('وضعیت اقدام نامعتبر است.');
        }
        $capa->setStatus($status);
        $this->audit->record(
            $incident->getTenant()->getId(),
            'incident',
            (string) $incident->getId(),
            'capa_status',
            $user->getId(),
            ['capaId' => $capaId, 'status' => $status],
        );
        $this->em->flush();

        return $this->json(['id' => $capaId, 'status' => $capa->getStatus()]);
    }
}

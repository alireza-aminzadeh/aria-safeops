<?php

namespace App\Controller;

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

final class IncidentStatusController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AuditLogger $audit,
    ) {
    }

    #[Route('/api/incidents/{id}/status', methods: ['POST'])]
    public function __invoke(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $incident = $this->em->find(Incident::class, Uuid::fromString($id));
        if (!$incident) {
            throw $this->createNotFoundException();
        }
        $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $next = (string) ($payload['status'] ?? '');
        if (!IncidentStatusMachine::can($incident->getStatus(), $next)) {
            throw new UnprocessableEntityHttpException('این گذار وضعیت حادثه مجاز نیست.');
        }
        if ($next === 'closed' && $incident->getCapaActions()->isEmpty()) {
            throw new UnprocessableEntityHttpException('بستن حادثه بدون حداقل یک اقدام اصلاحی مجاز نیست.');
        }

        $incident->setStatus($next);
        $this->audit->record(
            $incident->getTenant()->getId(),
            'incident',
            (string) $incident->getId(),
            'status_'.$next,
            $user->getId(),
            ['status' => $next],
        );
        $this->em->flush();

        return $this->json(['id' => (string) $incident->getId(), 'status' => $incident->getStatus()]);
    }
}

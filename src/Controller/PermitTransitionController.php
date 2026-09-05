<?php

namespace App\Controller;

use App\Domain\Permit\Entity\Permit;
use App\Domain\Shared\WorkflowTransitionApplier;
use App\Infrastructure\Mercure\MercurePublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\WorkflowInterface;

final class PermitTransitionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly WorkflowInterface $permitToWorkStateMachine,
        private readonly MercurePublisher $mercure,
    ) {
    }

    #[Route('/api/permits/{id}/available-transitions', methods: ['GET'])]
    public function available(string $id): JsonResponse
    {
        $permit = $this->em->find(Permit::class, Uuid::fromString($id));
        if (!$permit) {
            throw $this->createNotFoundException();
        }
        $enabled = [];
        foreach ($this->permitToWorkStateMachine->getEnabledTransitions($permit) as $transition) {
            $enabled[] = $transition->getName();
        }

        return $this->json(['status' => $permit->getStatus(), 'transitions' => $enabled]);
    }

    #[Route('/api/permits/{id}/transitions', methods: ['POST'])]
    public function apply(string $id, Request $request): JsonResponse
    {
        $permit = $this->em->find(Permit::class, Uuid::fromString($id));
        if (!$permit) {
            throw $this->createNotFoundException();
        }
        $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        WorkflowTransitionApplier::apply($this->permitToWorkStateMachine, $permit, (string) ($payload['transition'] ?? ''));
        $this->em->flush();
        $this->mercure->publish('permits', [
            'type' => 'permit.transition',
            'id' => (string) $permit->getId(),
            'status' => $permit->getStatus(),
        ]);

        return $this->json(['id' => (string) $permit->getId(), 'status' => $permit->getStatus()]);
    }
}

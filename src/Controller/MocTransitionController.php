<?php

namespace App\Controller;

use App\Domain\Moc\Entity\MocRequest;
use App\Domain\Shared\WorkflowTransitionApplier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\WorkflowInterface;

final class MocTransitionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly WorkflowInterface $mocWorkflowStateMachine,
    ) {
    }

    #[Route('/api/moc-requests/{id}/available-transitions', methods: ['GET'])]
    public function available(string $id): JsonResponse
    {
        $moc = $this->em->find(MocRequest::class, Uuid::fromString($id));
        if (!$moc) {
            throw $this->createNotFoundException();
        }
        $enabled = [];
        foreach ($this->mocWorkflowStateMachine->getEnabledTransitions($moc) as $transition) {
            $enabled[] = $transition->getName();
        }

        return $this->json(['status' => $moc->getStatus(), 'transitions' => $enabled]);
    }

    #[Route('/api/moc-requests/{id}/transitions', methods: ['POST'])]
    public function apply(string $id, Request $request): JsonResponse
    {
        $moc = $this->em->find(MocRequest::class, Uuid::fromString($id));
        if (!$moc) {
            throw $this->createNotFoundException();
        }
        $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        WorkflowTransitionApplier::apply($this->mocWorkflowStateMachine, $moc, (string) ($payload['transition'] ?? ''));
        $this->em->flush();

        return $this->json(['id' => (string) $moc->getId(), 'status' => $moc->getStatus()]);
    }
}

<?php

namespace App\Controller;

use App\Domain\Moc\Entity\MocRequest;
use App\Domain\Shared\Entity\User;
use App\Domain\Shared\WorkflowTransitionApplier;
use App\Infrastructure\Audit\ElectronicSignatureService;
use App\Infrastructure\Mercure\MercurePublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\WorkflowInterface;

final class MocTransitionController extends AbstractController
{
    /** @var list<string> گذارهایی که امضای الکترونیک روی آن‌ها الزامی است */
    private const SIGNATURE_REQUIRED = ['approve'];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly WorkflowInterface $mocWorkflowStateMachine,
        private readonly MercurePublisher $mercure,
        private readonly ElectronicSignatureService $signatures,
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
    public function apply(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $moc = $this->em->find(MocRequest::class, Uuid::fromString($id));
        if (!$moc) {
            throw $this->createNotFoundException();
        }
        $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $transition = (string) ($payload['transition'] ?? '');
        $signerName = trim((string) ($payload['signerName'] ?? ''));
        if (in_array($transition, self::SIGNATURE_REQUIRED, true) && $signerName === '') {
            throw new UnprocessableEntityHttpException('برای این گذار، امضای الکترونیک (تایپ نام کامل) الزامی است.');
        }

        WorkflowTransitionApplier::apply($this->mocWorkflowStateMachine, $moc, $transition);
        $this->em->flush();

        if (in_array($transition, self::SIGNATURE_REQUIRED, true)) {
            $this->signatures->sign($moc->getTenant()->getId(), 'moc_request', (string) $moc->getId(), $transition, $user->getId(), $signerName);
        }

        $this->mercure->publish('psm', [
            'type' => 'moc.transition',
            'id' => (string) $moc->getId(),
            'status' => $moc->getStatus(),
        ]);

        return $this->json(['id' => (string) $moc->getId(), 'status' => $moc->getStatus()]);
    }
}

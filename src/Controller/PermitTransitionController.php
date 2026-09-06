<?php

namespace App\Controller;

use App\Domain\Permit\Entity\Permit;
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

final class PermitTransitionController extends AbstractController
{
    /** @var list<string> گذارهایی که امضای الکترونیک روی آن‌ها الزامی است */
    private const SIGNATURE_REQUIRED = ['approve', 'activate'];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly WorkflowInterface $permitToWorkStateMachine,
        private readonly MercurePublisher $mercure,
        private readonly ElectronicSignatureService $signatures,
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
    public function apply(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $permit = $this->em->find(Permit::class, Uuid::fromString($id));
        if (!$permit) {
            throw $this->createNotFoundException();
        }
        $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $transition = (string) ($payload['transition'] ?? '');
        $signerName = trim((string) ($payload['signerName'] ?? ''));
        if (in_array($transition, self::SIGNATURE_REQUIRED, true) && $signerName === '') {
            throw new UnprocessableEntityHttpException('برای این گذار، امضای الکترونیک (تایپ نام کامل) الزامی است.');
        }

        WorkflowTransitionApplier::apply($this->permitToWorkStateMachine, $permit, $transition);
        $this->em->flush();

        if (in_array($transition, self::SIGNATURE_REQUIRED, true)) {
            $this->signatures->sign($permit->getTenant()->getId(), 'permit', (string) $permit->getId(), $transition, $user->getId(), $signerName);
        }

        $this->mercure->publish('permits', [
            'type' => 'permit.transition',
            'id' => (string) $permit->getId(),
            'status' => $permit->getStatus(),
        ]);

        return $this->json(['id' => (string) $permit->getId(), 'status' => $permit->getStatus()]);
    }
}

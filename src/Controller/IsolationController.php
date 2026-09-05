<?php

namespace App\Controller;

use App\Domain\Permit\Entity\Permit;
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

final class IsolationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AuditLogger $audit,
    ) {
    }

    #[Route('/api/permits/{id}/isolation', methods: ['POST'])]
    public function __invoke(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $permit = $this->em->find(Permit::class, Uuid::fromString($id));
        if (!$permit) {
            throw $this->createNotFoundException();
        }
        if ((string) $permit->getTenant()->getId() !== (string) $user->getTenant()->getId()) {
            throw $this->createAccessDeniedException();
        }
        if (!in_array($permit->getStatus(), ['draft', 'submitted', 'hse_review', 'approved'], true)) {
            throw new UnprocessableEntityHttpException('در این وضعیت نمی‌توان ایزولاسیون را تأیید کرد.');
        }

        $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $points = $payload['isolationPoints'] ?? [];
        if (!is_array($points) || $points === []) {
            throw new UnprocessableEntityHttpException('حداقل یک نقطهٔ ایزولاسیون لازم است.');
        }

        $permit->confirmIsolation($points, $user);
        $this->audit->record(
            $permit->getTenant()->getId(),
            'permit',
            (string) $permit->getId(),
            'loto_confirm',
            $user->getId(),
            ['points' => $permit->getIsolationPoints()],
        );
        $this->em->flush();

        return $this->json([
            'isolationConfirmed' => $permit->isIsolationConfirmed(),
            'isolationPoints' => $permit->getIsolationPoints(),
            'isolationConfirmedAt' => $permit->getIsolationConfirmedAt()?->format(\DateTimeInterface::ATOM),
        ]);
    }
}

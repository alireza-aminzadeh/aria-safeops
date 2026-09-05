<?php

namespace App\Controller;

use App\Domain\Permit\Entity\GasTestReading;
use App\Domain\Permit\Entity\Permit;
use App\Domain\Permit\GasTestPolicy;
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

final class GasTestController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AuditLogger $audit,
    ) {
    }

    #[Route('/api/permits/{id}/gas-test-readings', methods: ['POST'])]
    public function __invoke(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $permit = $this->em->find(Permit::class, Uuid::fromString($id));
        if (!$permit) {
            throw $this->createNotFoundException();
        }
        if ((string) $permit->getTenant()->getId() !== (string) $user->getTenant()->getId()) {
            throw $this->createAccessDeniedException();
        }

        $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $gasType = strtoupper((string) ($payload['gasType'] ?? ''));
        try {
            GasTestPolicy::assertType($gasType);
        } catch (\InvalidArgumentException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }

        $value = (string) ($payload['value'] ?? '');
        if (!is_numeric($value)) {
            throw new UnprocessableEntityHttpException('مقدار قرائت باید عددی باشد.');
        }

        $reading = new GasTestReading($permit, $gasType, $value, $user);
        $permit->addGasTestReading($reading);
        $this->em->persist($reading);
        $this->audit->record(
            $permit->getTenant()->getId(),
            'permit',
            (string) $permit->getId(),
            'gas_test',
            $user->getId(),
            ['gasType' => $gasType, 'value' => $value],
        );
        $this->em->flush();

        return $this->json([
            'id' => (string) $reading->getId(),
            'gasType' => $reading->getGasType(),
            'readingValue' => $reading->getReadingValue(),
            'recordedAt' => $reading->getRecordedAt()->format(\DateTimeInterface::ATOM),
        ], 201);
    }
}

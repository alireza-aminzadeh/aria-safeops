<?php

namespace App\Controller;

use App\Domain\Contractor\Entity\Contractor;
use App\Domain\Contractor\Entity\ContractorTrainingRecord;
use App\Domain\Shared\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

final class ContractorTrainingController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/api/contractors/{id}/trainings', methods: ['POST'])]
    public function __invoke(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_HSE_MANAGER');
        $contractor = $this->em->find(Contractor::class, Uuid::fromString($id));
        if (!$contractor) {
            throw $this->createNotFoundException();
        }
        $payload = json_decode($request->getContent(), true) ?? [];
        $code = trim((string) ($payload['courseCode'] ?? ''));
        $name = trim((string) ($payload['courseName'] ?? ''));
        if ($code === '' || $name === '') {
            throw new UnprocessableEntityHttpException('کد و نام دوره الزامی است.');
        }
        $record = new ContractorTrainingRecord(
            $contractor,
            $code,
            $name,
            new \DateTimeImmutable((string) ($payload['completedAt'] ?? 'today')),
            isset($payload['expiresAt']) && $payload['expiresAt'] ? new \DateTimeImmutable((string) $payload['expiresAt']) : null,
        );
        $contractor->addTraining($record);
        $this->em->persist($record);
        $this->em->flush();

        return $this->json([
            'id' => (string) $record->getId(),
            'courseCode' => $record->getCourseCode(),
            'courseName' => $record->getCourseName(),
            'completedAt' => $record->getCompletedAt()->format('Y-m-d'),
            'expiresAt' => $record->getExpiresAt()?->format('Y-m-d'),
            'expired' => $record->isExpired(),
        ], 201);
    }
}

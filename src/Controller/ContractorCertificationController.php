<?php

namespace App\Controller;

use App\Domain\Contractor\Entity\Contractor;
use App\Domain\Contractor\Entity\ContractorCertification;
use App\Domain\Shared\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

final class ContractorCertificationController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/api/contractors/{id}/certifications', methods: ['POST'])]
    public function __invoke(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_HSE_MANAGER');
        $contractor = $this->em->find(Contractor::class, Uuid::fromString($id));
        if (!$contractor) {
            throw $this->createNotFoundException();
        }
        $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $type = trim((string) ($payload['type'] ?? ''));
        $expires = (string) ($payload['expiresAt'] ?? '');
        if ($type === '' || $expires === '') {
            throw new UnprocessableEntityHttpException('نوع گواهی و تاریخ انقضا الزامی است.');
        }

        $cert = new ContractorCertification($contractor, $type, new \DateTimeImmutable($expires));
        $contractor->addCertification($cert);
        $this->em->persist($cert);
        $this->em->flush();

        return $this->json([
            'id' => (string) $cert->getId(),
            'type' => $cert->getType(),
            'expiresAt' => $cert->getExpiresAt()->format('Y-m-d'),
            'expiringSoon' => $cert->isExpiringSoon(),
        ], 201);
    }
}

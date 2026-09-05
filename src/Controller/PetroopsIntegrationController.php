<?php

namespace App\Controller;

use App\Domain\Integration\Entity\EquipmentHold;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class PetroopsIntegrationController
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[Route('/api/integrations/petroops/anomalies', methods: ['POST'])]
    public function ingest(Request $request): JsonResponse
    {
        $this->assertApiKey($request);
        $payload = json_decode($request->getContent(), true) ?? [];
        $tag = trim((string) ($payload['equipmentTag'] ?? ''));
        $eventId = trim((string) ($payload['eventId'] ?? ''));
        if ($tag === '' || $eventId === '') {
            return new JsonResponse(['message' => 'equipmentTag and eventId are required'], 400);
        }

        $detectedAt = new \DateTimeImmutable((string) ($payload['detectedAt'] ?? 'now'));
        $repo = $this->em->getRepository(EquipmentHold::class);
        $hold = $repo->findOneBy(['equipmentTag' => $tag]);
        $score = isset($payload['score']) ? (float) $payload['score'] : null;
        $summary = isset($payload['summary']) ? (string) $payload['summary'] : null;
        $status = (string) ($payload['status'] ?? 'open');

        if ($hold instanceof EquipmentHold) {
            $hold->refresh($eventId, $status, $detectedAt, $score, $summary);
        } else {
            $hold = new EquipmentHold($tag, $eventId, $status, $detectedAt, $score, $summary);
            $this->em->persist($hold);
        }
        $this->em->flush();

        return new JsonResponse([
            'accepted' => true,
            'equipmentTag' => $tag,
            'blocked' => $status === 'open',
        ]);
    }

    #[Route('/api/integrations/petroops/holds/{equipmentTag}', methods: ['GET'])]
    public function hold(Request $request, string $equipmentTag): JsonResponse
    {
        $this->assertApiKey($request);
        $hold = $this->em->getRepository(EquipmentHold::class)->findOneBy(['equipmentTag' => $equipmentTag]);
        if (!$hold instanceof EquipmentHold) {
            return new JsonResponse(['equipmentTag' => $equipmentTag, 'blocked' => false]);
        }

        return new JsonResponse([
            'equipmentTag' => $hold->getEquipmentTag(),
            'blocked' => $hold->getStatus() === 'open',
            'status' => $hold->getStatus(),
            'score' => $hold->getScore(),
            'summary' => $hold->getSummary(),
        ]);
    }

    private function assertApiKey(Request $request): void
    {
        $expected = (string) (
            getenv('PETROOPS_INTEGRATION_KEY')
            ?: ($_ENV['PETROOPS_INTEGRATION_KEY'] ?? $_SERVER['PETROOPS_INTEGRATION_KEY'] ?? '')
        );
        $provided = (string) $request->headers->get('X-Aria-Api-Key', '');
        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            throw new AccessDeniedHttpException('invalid integration key');
        }
    }
}

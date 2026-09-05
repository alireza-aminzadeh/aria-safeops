<?php

namespace App\Controller;

use App\Domain\Shared\Entity\AiQueryLog;
use App\Domain\Shared\Entity\User;
use App\Infrastructure\AiGateway\AiGatewayInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class AiController extends AbstractController
{
    public function __construct(
        private readonly AiGatewayInterface $gateway,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/api/ai/status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        $enabled = $this->gateway->isEnabled();

        return $this->json([
            'enabled' => $enabled,
            'available' => $enabled,
            'message' => $enabled
                ? 'دستیار دانش HSE روی همین سرور فعال است (بستهٔ محلی). اگر AI_GATEWAY_URL ست شود به سرویس مرکزی وصل می‌شود.'
                : 'سرویس دستیار هوشمند HSE غیرفعال است.',
        ]);
    }

    #[Route('/api/ai/knowledge-query', methods: ['POST'])]
    public function query(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $query = trim((string) ($payload['query'] ?? ''));
        $log = new AiQueryLog($user->getTenant()->getId(), $query, $user->getId());
        $answer = $this->gateway->askKnowledgeBase($query);
        $log->complete($answer->available ? 'ok' : 'unavailable', $answer->text ?? $answer->unavailableReason);
        $this->em->persist($log);
        $this->em->flush();

        if (!$answer->available) {
            return $this->json([
                'available' => false,
                'message' => $answer->unavailableReason,
                'citations' => [],
            ], 503);
        }

        return $this->json([
            'available' => true,
            'text' => $answer->text,
            'citations' => $answer->citations,
        ]);
    }

    #[Route('/api/ai/classify-risk', methods: ['POST'])]
    public function classify(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $result = $this->gateway->classifyRiskText((string) ($payload['text'] ?? ''));
        if (!$result->available) {
            return $this->json(['available' => false, 'message' => $result->unavailableReason], 503);
        }

        return $this->json(['available' => true, 'level' => $result->level]);
    }
}

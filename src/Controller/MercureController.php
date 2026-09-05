<?php

namespace App\Controller;

use App\Domain\Shared\Entity\User;
use App\Infrastructure\Mercure\MercurePublisher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class MercureController extends AbstractController
{
    public function __construct(private readonly MercurePublisher $mercure)
    {
    }

    #[Route('/api/mercure/config', methods: ['GET'])]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $topics = [
            'permits',
            'psm',
            'shift',
            'vision',
            'tenant/'.(string) $user->getTenant()->getId(),
        ];

        return $this->json([
            'enabled' => $this->mercure->isEnabled(),
            'hubUrl' => $this->mercure->publicUrl(),
            'token' => $this->mercure->isEnabled() ? $this->mercure->subscriberToken($topics) : null,
            'topics' => $topics,
        ]);
    }
}

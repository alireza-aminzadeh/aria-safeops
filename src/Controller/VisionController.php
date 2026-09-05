<?php

namespace App\Controller;

use App\Domain\Shared\Entity\User;
use App\Domain\Vision\Entity\VisionCamera;
use App\Domain\Vision\Entity\VisionEvent;
use App\Infrastructure\Mercure\MercurePublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

final class VisionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MercurePublisher $mercure,
    ) {
    }

    #[Route('/api/vision/overview', methods: ['GET'])]
    public function overview(#[CurrentUser] User $user): JsonResponse
    {
        $this->tickSimulator($user);
        $cameras = $this->em->getRepository(VisionCamera::class)->findBy(['tenant' => $user->getTenant()]);
        $events = $this->em->createQueryBuilder()
            ->select('e')
            ->from(VisionEvent::class, 'e')
            ->join('e.camera', 'c')
            ->andWhere('c.tenant = :tenant')
            ->setParameter('tenant', $user->getTenant())
            ->orderBy('e.detectedAt', 'DESC')
            ->setMaxResults(40)
            ->getQuery()
            ->getResult();

        return $this->json([
            'simulator' => getenv('VISION_SIMULATOR') !== 'false',
            'gatewayUrl' => (string) (getenv('VISION_GATEWAY_URL') ?: ''),
            'cameras' => array_map(static fn (VisionCamera $cam) => [
                'id' => (string) $cam->getId(),
                'name' => $cam->getName(),
                'area' => $cam->getArea(),
                'rtspUrl' => $cam->getRtspUrl(),
                'enabled' => $cam->isEnabled(),
            ], $cameras),
            'events' => array_map($this->event(...), $events),
        ]);
    }

    #[Route('/api/vision/cameras', methods: ['POST'])]
    public function addCamera(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_HSE_MANAGER');
        $payload = json_decode($request->getContent(), true) ?? [];
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            throw new UnprocessableEntityHttpException('نام دوربین الزامی است.');
        }
        $cam = new VisionCamera(
            $user->getTenant(),
            $name,
            (string) ($payload['area'] ?? 'process'),
            isset($payload['rtspUrl']) ? (string) $payload['rtspUrl'] : null,
        );
        $this->em->persist($cam);
        $this->em->flush();

        return $this->json([
            'id' => (string) $cam->getId(),
            'name' => $cam->getName(),
            'area' => $cam->getArea(),
            'rtspUrl' => $cam->getRtspUrl(),
            'enabled' => $cam->isEnabled(),
        ], 201);
    }

    #[Route('/api/vision/events/{id}/ack', methods: ['POST'])]
    public function ack(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $event = $this->em->find(VisionEvent::class, Uuid::fromString($id));
        if (!$event || (string) $event->getCamera()->getTenant()->getId() !== (string) $user->getTenant()->getId()) {
            throw $this->createNotFoundException();
        }
        $event->acknowledge();
        $this->em->flush();

        return $this->json($this->event($event));
    }

    #[Route('/api/vision/ingest', methods: ['POST'])]
    public function ingest(Request $request): JsonResponse
    {
        $expected = (string) (getenv('VISION_INGEST_KEY') ?: getenv('PETROOPS_INTEGRATION_KEY') ?: '');
        $provided = (string) $request->headers->get('X-Aria-Api-Key', '');
        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            throw new AccessDeniedHttpException('invalid vision ingest key');
        }
        $payload = json_decode($request->getContent(), true) ?? [];
        $cameraId = (string) ($payload['cameraId'] ?? '');
        $camera = $this->em->find(VisionCamera::class, Uuid::fromString($cameraId));
        if (!$camera) {
            throw $this->createNotFoundException();
        }
        $event = new VisionEvent(
            $camera,
            (string) ($payload['eventType'] ?? 'missing_ppe'),
            (float) ($payload['confidence'] ?? 0.8),
            (string) ($payload['summary'] ?? 'رویداد Vision'),
        );
        $this->em->persist($event);
        $this->em->flush();
        $this->mercure->publish('vision', ['type' => $event->getEventType(), 'id' => (string) $event->getId()]);

        return $this->json($this->event($event), 201);
    }

    private function tickSimulator(User $user): void
    {
        if (getenv('VISION_SIMULATOR') === 'false') {
            return;
        }
        $cameras = $this->em->getRepository(VisionCamera::class)->findBy([
            'tenant' => $user->getTenant(),
            'enabled' => true,
        ]);
        if ($cameras === []) {
            return;
        }
        $latest = $this->em->createQueryBuilder()
            ->select('e')
            ->from(VisionEvent::class, 'e')
            ->join('e.camera', 'c')
            ->andWhere('c.tenant = :tenant')
            ->setParameter('tenant', $user->getTenant())
            ->orderBy('e.detectedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        if ($latest instanceof VisionEvent && $latest->getDetectedAt() > new \DateTimeImmutable('-45 seconds')) {
            return;
        }
        $camera = $cameras[array_rand($cameras)];
        $type = random_int(0, 1) === 1 ? 'missing_ppe' : 'restricted_zone';
        $summary = $type === 'missing_ppe'
            ? 'کلاه یا عینک ایمنی در '.$camera->getArea().' دیده نشد.'
            : 'ورود به ناحیهٔ ممنوعه '.$camera->getArea().' بدون مجوز.';
        $event = new VisionEvent($camera, $type, 0.72 + (random_int(0, 20) / 100), $summary);
        $this->em->persist($event);
        $this->em->flush();
        $this->mercure->publish('vision', ['type' => $type, 'id' => (string) $event->getId()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function event(VisionEvent $event): array
    {
        return [
            'id' => (string) $event->getId(),
            'eventType' => $event->getEventType(),
            'confidence' => $event->getConfidence(),
            'summary' => $event->getSummary(),
            'status' => $event->getStatus(),
            'detectedAt' => $event->getDetectedAt()->format(DATE_ATOM),
            'camera' => [
                'id' => (string) $event->getCamera()->getId(),
                'name' => $event->getCamera()->getName(),
                'area' => $event->getCamera()->getArea(),
            ],
        ];
    }
}

<?php

namespace App\Controller;

use App\Domain\Moc\Entity\HazopRegisterItem;
use App\Domain\Psm\Entity\BowtieBarrier;
use App\Domain\Psm\Entity\LopaScenario;
use App\Domain\Shared\Entity\User;
use App\Infrastructure\Mercure\MercurePublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

final class HazopController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MercurePublisher $mercure,
    ) {
    }

    #[Route('/api/hazop-items', methods: ['GET'])]
    public function list(#[CurrentUser] User $user): JsonResponse
    {
        /** @var list<HazopRegisterItem> $items */
        $items = $this->em->getRepository(HazopRegisterItem::class)->findBy(
            ['tenant' => $user->getTenant()],
            ['riskRanking' => 'ASC'],
        );

        return $this->json(['items' => array_map($this->serialize(...), $items)]);
    }

    #[Route('/api/hazop-items', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_PERMIT_ISSUER');
        $payload = json_decode($request->getContent(), true) ?? [];
        $required = ['nodeDescription', 'deviation', 'cause', 'consequence', 'safeguards', 'riskRanking'];
        foreach ($required as $field) {
            if (trim((string) ($payload[$field] ?? '')) === '') {
                throw new UnprocessableEntityHttpException('همهٔ فیلدهای HAZOP الزامی است.');
            }
        }
        $item = new HazopRegisterItem(
            (string) $payload['nodeDescription'],
            (string) $payload['deviation'],
            (string) $payload['cause'],
            (string) $payload['consequence'],
            (string) $payload['safeguards'],
            (string) $payload['riskRanking'],
        );
        $item->setTenant($user->getTenant());
        $item->setEquipmentTag(isset($payload['equipmentTag']) ? (string) $payload['equipmentTag'] : null);
        $this->em->persist($item);
        $this->em->flush();
        $this->mercure->publish('psm', ['type' => 'hazop.created', 'id' => (string) $item->getId()]);

        return $this->json($this->serialize($item), 201);
    }

    #[Route('/api/hazop-items/{id}/lopa', methods: ['POST'])]
    public function addLopa(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_HSE_MANAGER');
        $item = $this->item($id, $user);
        $payload = json_decode($request->getContent(), true) ?? [];
        $scenario = new LopaScenario(
            $item,
            trim((string) ($payload['initiatingEvent'] ?? '')),
            (int) ($payload['iplCount'] ?? 0),
            (string) ($payload['targetFrequency'] ?? '1e-4'),
            (string) ($payload['residualRisk'] ?? 'medium'),
        );
        if ($scenario->getInitiatingEvent() === '') {
            throw new UnprocessableEntityHttpException('رویداد آغازین LOPA الزامی است.');
        }
        $item->addLopa($scenario);
        $this->em->persist($scenario);
        $this->em->flush();

        return $this->json($this->serialize($item), 201);
    }

    #[Route('/api/hazop-items/{id}/barriers', methods: ['POST'])]
    public function addBarrier(string $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_HSE_MANAGER');
        $item = $this->item($id, $user);
        $payload = json_decode($request->getContent(), true) ?? [];
        $side = (string) ($payload['side'] ?? 'prevention');
        if (!in_array($side, ['prevention', 'mitigation'], true)) {
            throw new UnprocessableEntityHttpException('سمت مانع باید prevention یا mitigation باشد.');
        }
        $barrier = new BowtieBarrier(
            $item,
            $side,
            trim((string) ($payload['description'] ?? '')),
            (string) ($payload['effectiveness'] ?? 'medium'),
            (string) ($payload['status'] ?? 'in_place'),
        );
        if ($barrier->getDescription() === '') {
            throw new UnprocessableEntityHttpException('شرح مانع الزامی است.');
        }
        $item->addBarrier($barrier);
        $this->em->persist($barrier);
        $this->em->flush();

        return $this->json($this->serialize($item), 201);
    }

    private function item(string $id, User $user): HazopRegisterItem
    {
        $item = $this->em->find(HazopRegisterItem::class, Uuid::fromString($id));
        if (!$item || (string) $item->getTenant()?->getId() !== (string) $user->getTenant()->getId()) {
            throw $this->createNotFoundException();
        }

        return $item;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(HazopRegisterItem $item): array
    {
        return [
            'id' => (string) $item->getId(),
            'equipmentTag' => $item->getEquipmentTag(),
            'nodeDescription' => $item->getNodeDescription(),
            'deviation' => $item->getDeviation(),
            'cause' => $item->getCause(),
            'consequence' => $item->getConsequence(),
            'safeguards' => $item->getSafeguards(),
            'riskRanking' => $item->getRiskRanking(),
            'status' => $item->getStatus(),
            'lopa' => $item->getLopaScenarios()->map(static fn (LopaScenario $row) => [
                'id' => (string) $row->getId(),
                'initiatingEvent' => $row->getInitiatingEvent(),
                'iplCount' => $row->getIplCount(),
                'targetFrequency' => $row->getTargetFrequency(),
                'residualRisk' => $row->getResidualRisk(),
            ])->toArray(),
            'barriers' => $item->getBarriers()->map(static fn (BowtieBarrier $row) => [
                'id' => (string) $row->getId(),
                'side' => $row->getSide(),
                'description' => $row->getDescription(),
                'effectiveness' => $row->getEffectiveness(),
                'status' => $row->getStatus(),
            ])->toArray(),
        ];
    }
}

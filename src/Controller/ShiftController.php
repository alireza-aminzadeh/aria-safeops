<?php

namespace App\Controller;

use App\Domain\Shared\Entity\User;
use App\Domain\Shift\Entity\LogbookEntry;
use App\Domain\Shift\Entity\ShiftHandover;
use App\Domain\Shift\Entity\ToolboxTalk;
use App\Infrastructure\Mercure\MercurePublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

final class ShiftController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MercurePublisher $mercure,
    ) {
    }

    #[Route('/api/shift-handovers', methods: ['GET'])]
    public function handovers(#[CurrentUser] User $user): JsonResponse
    {
        $items = $this->em->getRepository(ShiftHandover::class)->findBy(
            ['tenant' => $user->getTenant()],
            ['createdAt' => 'DESC'],
            50,
        );

        return $this->json(['items' => array_map($this->handover(...), $items)]);
    }

    #[Route('/api/shift-handovers', methods: ['POST'])]
    public function createHandover(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $summary = trim((string) ($payload['summary'] ?? ''));
        if ($summary === '') {
            throw new UnprocessableEntityHttpException('خلاصهٔ تحویل شیفت الزامی است.');
        }
        $row = new ShiftHandover(
            $user->getTenant(),
            new \DateTimeImmutable((string) ($payload['shiftDate'] ?? 'today')),
            (string) ($payload['shiftName'] ?? 'day'),
            (string) ($payload['outgoingName'] ?? $user->getFullName()),
            (string) ($payload['incomingName'] ?? ''),
            $summary,
            isset($payload['outstandingWork']) ? (string) $payload['outstandingWork'] : null,
        );
        $row->setCreatedBy($user);
        $this->em->persist($row);
        $this->em->flush();

        return $this->json($this->handover($row), 201);
    }

    #[Route('/api/shift-handovers/{id}/submit', methods: ['POST'])]
    public function submit(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $row = $this->handoverEntity($id, $user);
        $row->submit();
        $this->em->flush();
        $this->mercure->publish('shift', ['type' => 'handover.submitted', 'id' => (string) $row->getId()]);

        return $this->json($this->handover($row));
    }

    #[Route('/api/shift-handovers/{id}/accept', methods: ['POST'])]
    public function accept(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $row = $this->handoverEntity($id, $user);
        if ($row->getStatus() !== 'submitted') {
            throw new UnprocessableEntityHttpException('فقط تحویل ارسال‌شده قابل پذیرش است.');
        }
        $row->accept();
        $this->em->flush();
        $this->mercure->publish('shift', ['type' => 'handover.accepted', 'id' => (string) $row->getId()]);

        return $this->json($this->handover($row));
    }

    #[Route('/api/logbook', methods: ['GET'])]
    public function logbook(#[CurrentUser] User $user): JsonResponse
    {
        $items = $this->em->getRepository(LogbookEntry::class)->findBy(
            ['tenant' => $user->getTenant()],
            ['createdAt' => 'DESC'],
            80,
        );

        return $this->json(['items' => array_map(static fn (LogbookEntry $row) => [
            'id' => (string) $row->getId(),
            'category' => $row->getCategory(),
            'body' => $row->getBody(),
            'authorName' => $row->getAuthorName(),
            'createdAt' => $row->getCreatedAt()->format(DATE_ATOM),
        ], $items)]);
    }

    #[Route('/api/logbook', methods: ['POST'])]
    public function addLog(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $body = trim((string) ($payload['body'] ?? ''));
        if ($body === '') {
            throw new UnprocessableEntityHttpException('متن لاگ‌بوک الزامی است.');
        }
        $row = new LogbookEntry(
            $user->getTenant(),
            (string) ($payload['category'] ?? 'operations'),
            $body,
            $user->getFullName(),
            $user,
        );
        $this->em->persist($row);
        $this->em->flush();

        return $this->json([
            'id' => (string) $row->getId(),
            'category' => $row->getCategory(),
            'body' => $row->getBody(),
            'authorName' => $row->getAuthorName(),
            'createdAt' => $row->getCreatedAt()->format(DATE_ATOM),
        ], 201);
    }

    #[Route('/api/toolbox-talks', methods: ['GET'])]
    public function talks(#[CurrentUser] User $user): JsonResponse
    {
        $items = $this->em->getRepository(ToolboxTalk::class)->findBy(
            ['tenant' => $user->getTenant()],
            ['heldAt' => 'DESC'],
            50,
        );

        return $this->json(['items' => array_map($this->talk(...), $items)]);
    }

    #[Route('/api/toolbox-talks', methods: ['POST'])]
    public function addTalk(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $topic = trim((string) ($payload['topic'] ?? ''));
        if ($topic === '') {
            throw new UnprocessableEntityHttpException('موضوع Toolbox Talk الزامی است.');
        }
        $row = new ToolboxTalk(
            $user->getTenant(),
            $topic,
            (string) ($payload['location'] ?? 'سایت'),
            new \DateTimeImmutable((string) ($payload['heldAt'] ?? 'now')),
            (string) ($payload['leaderName'] ?? $user->getFullName()),
            (int) ($payload['attendeeCount'] ?? 0),
            isset($payload['notes']) ? (string) $payload['notes'] : null,
        );
        $this->em->persist($row);
        $this->em->flush();

        return $this->json($this->talk($row), 201);
    }

    private function handoverEntity(string $id, User $user): ShiftHandover
    {
        $row = $this->em->find(ShiftHandover::class, Uuid::fromString($id));
        if (!$row || (string) $row->getTenant()->getId() !== (string) $user->getTenant()->getId()) {
            throw $this->createNotFoundException();
        }

        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    private function handover(ShiftHandover $row): array
    {
        return [
            'id' => (string) $row->getId(),
            'shiftDate' => $row->getShiftDate()->format('Y-m-d'),
            'shiftName' => $row->getShiftName(),
            'outgoingName' => $row->getOutgoingName(),
            'incomingName' => $row->getIncomingName(),
            'summary' => $row->getSummary(),
            'outstandingWork' => $row->getOutstandingWork(),
            'status' => $row->getStatus(),
            'acceptedAt' => $row->getAcceptedAt()?->format(DATE_ATOM),
            'createdAt' => $row->getCreatedAt()->format(DATE_ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function talk(ToolboxTalk $row): array
    {
        return [
            'id' => (string) $row->getId(),
            'topic' => $row->getTopic(),
            'location' => $row->getLocation(),
            'heldAt' => $row->getHeldAt()->format(DATE_ATOM),
            'leaderName' => $row->getLeaderName(),
            'attendeeCount' => $row->getAttendeeCount(),
            'notes' => $row->getNotes(),
        ];
    }
}

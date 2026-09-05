<?php

namespace App\Domain\Moc\Workflow;

use App\Domain\Moc\Entity\MocRequest;
use App\Domain\Moc\Voter\MocVoter;
use App\Domain\Shared\Entity\User;
use App\Infrastructure\Audit\AuditLogger;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\GuardEvent;

final class MocWorkflowSubscriber
{
    public function __construct(
        private readonly Security $security,
        private readonly AuditLogger $audit,
    ) {
    }

    #[AsEventListener(event: 'workflow.moc_workflow.guard')]
    public function guard(GuardEvent $event): void
    {
        /** @var MocRequest $moc */
        $moc = $event->getSubject();
        $name = $event->getTransition()->getName();
        $attribute = match ($name) {
            'approve', 'reject' => MocVoter::APPROVE,
            'close' => MocVoter::CLOSE,
            default => MocVoter::UPDATE,
        };

        if (!$this->security->isGranted($attribute, $moc)) {
            $event->setBlocked(true, 'شما مجاز به این گذار نیستید.');
        }
    }

    #[AsEventListener(event: 'workflow.moc_workflow.completed')]
    public function completed(CompletedEvent $event): void
    {
        /** @var MocRequest $moc */
        $moc = $event->getSubject();
        $user = $this->security->getUser();
        $this->audit->record(
            $moc->getTenant()->getId(),
            'moc_request',
            (string) $moc->getId(),
            $event->getTransition()?->getName() ?? 'unknown',
            $user instanceof User ? $user->getId() : null,
            ['status' => $moc->getStatus()],
        );
    }
}

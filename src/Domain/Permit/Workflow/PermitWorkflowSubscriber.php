<?php

namespace App\Domain\Permit\Workflow;

use App\Domain\Integration\EquipmentHoldChecker;
use App\Domain\Permit\Entity\Permit;
use App\Domain\Permit\GasTestPolicy;
use App\Domain\Permit\IsolationPolicy;
use App\Domain\Permit\Voter\PermitVoter;
use App\Domain\Shared\Entity\User;
use App\Infrastructure\Audit\AuditLogger;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\GuardEvent;

final class PermitWorkflowSubscriber
{
    /** @var list<string> گذارهایی که فعالیت فیزیکی روی تجهیز را از سر می‌گیرند و باید نگه‌داشت PetroOps را دوباره بررسی کنند */
    private const EQUIPMENT_HOLD_GUARDED_TRANSITIONS = ['activate', 'resume'];

    public function __construct(
        private readonly Security $security,
        private readonly AuditLogger $audit,
        private readonly PermitConflictChecker $conflicts,
        private readonly EquipmentHoldChecker $equipmentHold,
    ) {
    }

    #[AsEventListener(event: 'workflow.permit_to_work.guard')]
    public function guard(GuardEvent $event): void
    {
        /** @var Permit $permit */
        $permit = $event->getSubject();
        $name = $event->getTransition()->getName();
        $attribute = match ($name) {
            'approve' => PermitVoter::APPROVE,
            'reject' => PermitVoter::REJECT,
            'activate', 'resume' => PermitVoter::ACTIVATE,
            'suspend' => PermitVoter::SUSPEND,
            'close' => PermitVoter::CLOSE,
            default => PermitVoter::ISSUE,
        };

        if (!$this->security->isGranted($attribute, $permit)) {
            $event->setBlocked(true, 'شما مجاز به این گذار نیستید.');
            return;
        }

        if ($name === 'submit' && trim($permit->getWorkDescription()) === '') {
            $event->setBlocked(true, 'شرح کار برای ارسال مجوز الزامی است.');
            return;
        }

        // نگه‌داشت PetroOps ممکن است *بعد* از صدور مجوز باز شود؛ پس باید هم روی
        // فعال‌سازی و هم روی از سرگیری (بعد از تعلیق) دوباره بررسی شود، نه فقط
        // یک‌بار در لحظهٔ ایجاد (TenantAwarePersistProcessor).
        if (in_array($name, self::EQUIPMENT_HOLD_GUARDED_TRANSITIONS, true)) {
            if ($blocker = $this->equipmentHold->blockerMessage($permit->getEquipmentTag())) {
                $event->setBlocked(true, $blocker);
                return;
            }
        }

        if ($name !== 'activate') {
            return;
        }

        if ($blocker = GasTestPolicy::activationBlocker($permit)) {
            $event->setBlocked(true, $blocker);
            return;
        }
        if ($blocker = IsolationPolicy::activationBlocker($permit)) {
            $event->setBlocked(true, $blocker);
            return;
        }
        if ($this->conflicts->hasActiveConflict($permit)) {
            $event->setBlocked(true, 'مجوز فعال دیگری با تعارض SIMOPS روی این تجهیز یا ناحیه باز است.');
        }
    }

    #[AsEventListener(event: 'workflow.permit_to_work.completed')]
    public function completed(CompletedEvent $event): void
    {
        /** @var Permit $permit */
        $permit = $event->getSubject();
        $user = $this->security->getUser();
        if ($event->getTransition()?->getName() === 'approve' && $user instanceof User) {
            $permit->setApprovedBy($user);
        }

        $this->audit->record(
            $permit->getTenant()->getId(),
            'permit',
            (string) $permit->getId(),
            $event->getTransition()?->getName() ?? 'unknown',
            $user instanceof User ? $user->getId() : null,
            ['status' => $permit->getStatus()],
        );
    }
}

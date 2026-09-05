<?php

namespace App\Domain\Shared;

use App\Domain\Moc\Entity\MocRequest;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Workflow\WorkflowInterface;

final class WorkflowTransitionApplier
{
    public static function apply(WorkflowInterface $workflow, object $subject, string $transition): void
    {
        if ($transition === '' || !$workflow->can($subject, $transition)) {
            throw new UnprocessableEntityHttpException(self::humanMessage($workflow, $subject, $transition));
        }

        $workflow->apply($subject, $transition);
    }

    private static function humanMessage(WorkflowInterface $workflow, object $subject, string $transition): string
    {
        if ($subject instanceof MocRequest && $transition === 'close' && $subject->getStatus() !== 'pssr') {
            return 'بستن MOC بدون تکمیل PSSR مجاز نیست.';
        }

        $reasons = [];
        if ($transition !== '') {
            try {
                foreach ($workflow->buildTransitionBlockerList($subject, $transition) as $blocker) {
                    $message = $blocker->getMessage();
                    if ($message !== '' && !self::isGenericWorkflowMessage($message)) {
                        $reasons[] = $message;
                    }
                }
            } catch (\Throwable) {
            }
        }

        return $reasons !== [] ? implode(' ', $reasons) : 'این گذار در وضعیت فعلی مجاز نیست.';
    }

    private static function isGenericWorkflowMessage(string $message): bool
    {
        return str_contains($message, 'The marking does not enable the transition')
            || str_contains($message, 'The transition cannot be applied');
    }
}

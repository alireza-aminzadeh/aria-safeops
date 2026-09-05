<?php

namespace App\Domain\Permit;

use App\Domain\Permit\Entity\Permit;

final class IsolationPolicy
{
    public static function activationBlocker(Permit $permit): ?string
    {
        if (!$permit->getPermitType()->requiresIsolation()) {
            return null;
        }

        if (!$permit->isIsolationConfirmed()) {
            return 'قبل از فعال‌سازی باید ایزولاسیون انرژی (LOTO) تأیید شود.';
        }

        return null;
    }
}

<?php

namespace App\Domain\Permit;

use App\Domain\Permit\Entity\Permit;

final class GasTestPolicy
{
    public const ALLOWED_TYPES = ['O2', 'LEL', 'H2S', 'CO'];
    public const REQUIRED_TYPES = ['O2', 'LEL', 'H2S'];

    public static function assertType(string $gasType): void
    {
        if (!in_array($gasType, self::ALLOWED_TYPES, true)) {
            throw new \InvalidArgumentException('نوع گاز نامعتبر است. مقادیر مجاز: O2, LEL, H2S, CO');
        }
    }

    public static function activationBlocker(Permit $permit): ?string
    {
        if (!$permit->getPermitType()->requiresGasTest()) {
            return null;
        }

        $latest = [];
        foreach ($permit->getGasTestReadings() as $reading) {
            $latest[$reading->getGasType()] = (float) $reading->getReadingValue();
        }

        foreach (self::REQUIRED_TYPES as $gas) {
            if (!isset($latest[$gas])) {
                return sprintf('برای فعال‌سازی این نوع مجوز، قرائت %s الزامی است.', $gas);
            }
        }

        if ($latest['O2'] < 19.5 || $latest['O2'] > 23.5) {
            return 'اکسیژن باید بین ۱۹٫۵٪ و ۲۳٫۵٪ باشد.';
        }
        if ($latest['LEL'] > 10) {
            return 'LEL باید حداکثر ۱۰٪ باشد.';
        }
        if ($latest['H2S'] > 10) {
            return 'H2S باید حداکثر ۱۰ ppm باشد.';
        }
        if (isset($latest['CO']) && $latest['CO'] > 25) {
            return 'CO باید حداکثر ۲۵ ppm باشد.';
        }

        return null;
    }
}

<?php

namespace App\Domain\Emergency;

/**
 * ارزیابی انطباق پارامترهای پساب/انتشار با حد مجاز. برای pH یک بازهٔ متقارن
 * حول حد ثبت‌شده در نظر گرفته می‌شود (طبق رویهٔ استاندارد ۶ تا ۹)؛ برای بقیهٔ
 * پارامترها (BOD/COD/TSS/روغن‌وچربی و…) قاعدهٔ «حداکثر مجاز» است.
 */
final class EffluentCompliancePolicy
{
    public static function isCompliant(string $parameter, float $value, ?float $limit): bool
    {
        if ($limit === null) {
            return true;
        }
        if (strtolower($parameter) === 'ph') {
            return $value >= 6.0 && $value <= 9.0;
        }

        return $value <= $limit;
    }

    /**
     * حدهای مرجع پیش‌فرض (mg/L مگر خلاف آن ذکر شود) — صرفاً برای پیش‌پر کردن
     * فرم؛ کاربر می‌تواند حد واقعی مصوب سایت را جای‌گزین کند.
     *
     * @return array<string, float>
     */
    public static function referenceLimits(): array
    {
        return [
            'BOD' => 30.0,
            'COD' => 60.0,
            'TSS' => 40.0,
            'Oil_Grease' => 10.0,
            'pH' => 9.0,
        ];
    }
}

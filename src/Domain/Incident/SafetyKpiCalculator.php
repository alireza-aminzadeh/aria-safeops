<?php

namespace App\Domain\Incident;

/**
 * Lagging safety indicators per API RP 754 / OSHA recordkeeping convention.
 *
 * LTIFR — Lost Time Injury Frequency Rate: تعداد آسیب‌های منجر به ازدست‌رفتن روز کار
 *         به‌ازای هر یک میلیون ساعت‌کار (استاندارد ISO/API متداول در صنعت نفت‌وگاز).
 * TRIR  — Total Recordable Incident Rate: تعداد رویدادهای ثبت‌شدنی به‌ازای هر
 *         ۲۰۰٬۰۰۰ ساعت‌کار (معادل ۱۰۰ نفر × ۴۰ ساعت × ۵۰ هفته — قرارداد OSHA).
 */
final class SafetyKpiCalculator
{
    public const LTIFR_BASE_HOURS = 1_000_000;
    public const TRIR_BASE_HOURS = 200_000;

    public static function ltifr(int $lostTimeInjuries, float $hoursWorked): float
    {
        if ($hoursWorked <= 0.0) {
            return 0.0;
        }

        return round(($lostTimeInjuries * self::LTIFR_BASE_HOURS) / $hoursWorked, 2);
    }

    public static function trir(int $recordableCount, float $hoursWorked): float
    {
        if ($hoursWorked <= 0.0) {
            return 0.0;
        }

        return round(($recordableCount * self::TRIR_BASE_HOURS) / $hoursWorked, 2);
    }

    /**
     * یک رویداد ثبت‌شدنی (Recordable) پیش‌فرض — طبق قرارداد OSHA 1904: هر آسیب یا
     * near-miss با شدت بالا هم رویداد قابل‌ثبت محسوب می‌شود؛ HSE Manager می‌تواند
     * بعداً این تصمیم را دستی روی هر حادثه بازنویسی کند (فیلد recordable در PATCH).
     */
    public static function isRecordableByDefault(string $type, string $severity): bool
    {
        if ($type === 'injury') {
            return true;
        }
        if ($type === 'incident' && in_array($severity, ['medium', 'high', 'critical'], true)) {
            return true;
        }

        return false;
    }
}

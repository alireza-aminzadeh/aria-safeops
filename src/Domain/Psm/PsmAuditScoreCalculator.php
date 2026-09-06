<?php

namespace App\Domain\Psm;

final class PsmAuditScoreCalculator
{
    /** @var array<string, float> */
    private const RATING_WEIGHT = [
        'compliant' => 100.0,
        'partial' => 50.0,
        'non_compliant' => 0.0,
    ];

    /**
     * درصد کلی انطباق — موارد «قابل‌اجرا نیست» (not_applicable) از مخرج کسر می‌شوند.
     *
     * @param list<string> $ratings
     */
    public static function overallPercent(array $ratings): ?float
    {
        $applicable = array_filter($ratings, static fn (string $r) => $r !== 'not_applicable');
        if ($applicable === []) {
            return null;
        }
        $sum = array_sum(array_map(static fn (string $r) => self::RATING_WEIGHT[$r] ?? 0.0, $applicable));

        return round($sum / count($applicable), 1);
    }
}

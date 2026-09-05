<?php

namespace App\Domain\Psm;

final class Api754KpiCalculator
{
    /**
     * Map an HSE incident to API RP 754 process-safety event tiers.
     * Tier 1/2 are lagging LOPC-style events; 3/4 are leading indicators.
     */
    public static function tier(string $type, string $severity): int
    {
        if ($type === 'injury' && in_array($severity, ['high', 'critical'], true)) {
            return 1;
        }
        if ($type === 'incident' && $severity === 'critical') {
            return 1;
        }
        if ($type === 'incident' && $severity === 'high') {
            return 2;
        }
        if ($type === 'near_miss' && in_array($severity, ['high', 'critical'], true)) {
            return 3;
        }

        return 4;
    }

    /**
     * @param list<array{type: string, severity: string}> $incidents
     * @return array{tier1: int, tier2: int, tier3: int, tier4: int}
     */
    public static function counts(array $incidents): array
    {
        $out = ['tier1' => 0, 'tier2' => 0, 'tier3' => 0, 'tier4' => 0];
        foreach ($incidents as $incident) {
            $tier = self::tier($incident['type'], $incident['severity']);
            $out['tier'.$tier]++;
        }

        return $out;
    }
}

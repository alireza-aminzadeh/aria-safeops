<?php

namespace App\Domain\Incident;

final class IncidentStatusMachine
{
    /** @var array<string, list<string>> */
    public const NEXT = [
        'reported' => ['under_investigation'],
        'under_investigation' => ['capa_assigned'],
        'capa_assigned' => ['closed'],
        'closed' => [],
    ];

    public static function can(string $from, string $to): bool
    {
        return in_array($to, self::NEXT[$from] ?? [], true);
    }

    public static function afterCapaAdded(string $current): string
    {
        if (in_array($current, ['reported', 'under_investigation'], true)) {
            return 'capa_assigned';
        }

        return $current;
    }
}

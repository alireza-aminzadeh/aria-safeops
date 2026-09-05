<?php

namespace App\Domain\Shared;

final class OperatorCredentials
{
    public const USERNAME = 'alireza';
    public const LOCAL_PASSWORD = 'alireza';
    public const PRODUCTION_PASSWORD = 'Aria7x!Alireza#Ops2026';

    public static function passwordFromEnvironment(): string
    {
        $explicit = self::env('SEED_ALIREZA_PASSWORD');
        if ($explicit !== '') {
            return $explicit;
        }

        return self::env('ARIA_RUNTIME') === 'production'
            ? self::PRODUCTION_PASSWORD
            : self::LOCAL_PASSWORD;
    }

    private static function env(string $name): string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        return is_string($value) ? trim($value) : '';
    }
}

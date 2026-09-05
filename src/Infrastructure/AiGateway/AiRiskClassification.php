<?php

namespace App\Infrastructure\AiGateway;

final class AiRiskClassification
{
    private function __construct(
        public readonly bool $available,
        public readonly ?string $level,
        public readonly ?string $unavailableReason,
    ) {
    }

    public static function unavailable(): self
    {
        return new self(false, null, 'طبقه‌بندی ریسک هوشمند هنوز فعال نشده است.');
    }
}

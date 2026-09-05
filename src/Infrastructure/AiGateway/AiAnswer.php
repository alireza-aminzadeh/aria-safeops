<?php

namespace App\Infrastructure\AiGateway;

final class AiAnswer
{
    private function __construct(
        public readonly bool $available,
        public readonly ?string $text,
        public readonly array $citations,
        public readonly ?string $unavailableReason,
    ) {
    }

    public static function unavailable(string $reason): self
    {
        return new self(false, null, [], $reason);
    }

    public static function fromResponse(string $text, array $citations): self
    {
        return new self(true, $text, $citations, null);
    }
}

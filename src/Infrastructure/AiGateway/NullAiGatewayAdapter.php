<?php

namespace App\Infrastructure\AiGateway;

final class NullAiGatewayAdapter implements AiGatewayInterface
{
    public function isEnabled(): bool
    {
        return false;
    }

    public function askKnowledgeBase(string $query, array $context = []): AiAnswer
    {
        return AiAnswer::unavailable('سرویس دستیار هوشمند HSE هنوز فعال نشده است.');
    }

    public function classifyRiskText(string $text, array $context = []): AiRiskClassification
    {
        return AiRiskClassification::unavailable();
    }
}

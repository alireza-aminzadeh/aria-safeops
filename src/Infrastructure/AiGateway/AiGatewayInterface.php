<?php

namespace App\Infrastructure\AiGateway;

interface AiGatewayInterface
{
    public function isEnabled(): bool;

    public function askKnowledgeBase(string $query, array $context = []): AiAnswer;

    public function classifyRiskText(string $text, array $context = []): AiRiskClassification;
}

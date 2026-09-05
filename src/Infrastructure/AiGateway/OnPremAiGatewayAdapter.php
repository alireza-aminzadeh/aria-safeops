<?php

namespace App\Infrastructure\AiGateway;

final class OnPremAiGatewayAdapter implements AiGatewayInterface
{
    public function isEnabled(): bool
    {
        return true;
    }

    public function askKnowledgeBase(string $query, array $context = []): AiAnswer
    {
        $docs = HseKnowledgePack::retrieve($query);
        if ($docs === []) {
            $docs = array_slice(HseKnowledgePack::documents(), 0, 1);
        }
        $text = implode("\n\n", array_map(
            static fn (array $doc) => $doc['title'].': '.$doc['text'],
            $docs,
        ));
        $citations = array_map(static fn (array $doc) => $doc['citation'], $docs);

        return AiAnswer::fromResponse($text, $citations);
    }

    public function classifyRiskText(string $text, array $context = []): AiRiskClassification
    {
        $hay = mb_strtolower($text);
        if (preg_match('/گاز|h2s|hot work|کار گرم|فضای بسته|confined|loto|انفجار/u', $hay)) {
            return AiRiskClassification::fromLevel('high');
        }
        if (preg_match('/ارتفاع|حفاری|برق|excavation|height/u', $hay)) {
            return AiRiskClassification::fromLevel('medium');
        }

        return AiRiskClassification::fromLevel('low');
    }
}

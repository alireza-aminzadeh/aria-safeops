<?php

namespace App\Infrastructure\AiGateway;

final class HttpAiGatewayAdapter implements AiGatewayInterface
{
    public function isEnabled(): bool
    {
        return $this->baseUrl() !== '';
    }

    public function askKnowledgeBase(string $query, array $context = []): AiAnswer
    {
        $payload = $this->post('/v1/knowledge-query', ['query' => $query, 'context' => $context]);
        if ($payload === null) {
            return AiAnswer::unavailable('سرویس AI مرکزی پاسخ نداد.');
        }
        $text = (string) ($payload['text'] ?? $payload['answer'] ?? '');
        $citations = is_array($payload['citations'] ?? null) ? $payload['citations'] : [];

        return $text === '' ? AiAnswer::unavailable('پاسخ خالی از AI مرکزی.') : AiAnswer::fromResponse($text, $citations);
    }

    public function classifyRiskText(string $text, array $context = []): AiRiskClassification
    {
        $payload = $this->post('/v1/classify-risk', ['text' => $text, 'context' => $context]);
        if ($payload === null) {
            return AiRiskClassification::unavailable();
        }

        return AiRiskClassification::fromLevel((string) ($payload['level'] ?? 'medium'));
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>|null
     */
    private function post(string $path, array $body): ?array
    {
        $url = $this->baseUrl().$path;
        $headers = "Content-Type: application/json\r\nAccept: application/json\r\n";
        $key = (string) (getenv('AI_GATEWAY_API_KEY') ?: '');
        if ($key !== '') {
            $headers .= "Authorization: Bearer {$key}\r\n";
        }
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => $headers,
                'content' => json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'timeout' => (int) (getenv('AI_GATEWAY_TIMEOUT_MS') ?: 8000) / 1000,
                'ignore_errors' => true,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            return null;
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function baseUrl(): string
    {
        return rtrim((string) (getenv('AI_GATEWAY_URL') ?: ''), '/');
    }
}

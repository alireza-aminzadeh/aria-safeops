<?php

namespace App\Infrastructure\Mercure;

use Psr\Log\LoggerInterface;

final class MercurePublisher
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function isEnabled(): bool
    {
        return getenv('MERCURE_ENABLED') === 'true' && $this->secret() !== '' && $this->hub() !== '';
    }

    /**
     * @param array<string, mixed> $data
     */
    public function publish(string $topic, array $data): void
    {
        if (!$this->isEnabled()) {
            return;
        }
        $token = Hs256Jwt::encode([
            'mercure' => ['publish' => ['*']],
            'exp' => time() + 60,
        ], $this->secret());
        $body = http_build_query([
            'topic' => $topic,
            'data' => json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ]);
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Authorization: Bearer {$token}\r\nContent-Type: application/x-www-form-urlencoded\r\n",
                'content' => $body,
                'timeout' => 2,
                'ignore_errors' => true,
            ],
        ]);
        $result = @file_get_contents($this->hub(), false, $ctx);
        if ($result === false) {
            $this->logger->warning('Mercure publish failed for {topic}', ['topic' => $topic]);
        }
    }

    public function subscriberToken(array $topics): string
    {
        return Hs256Jwt::encode([
            'mercure' => ['subscribe' => $topics],
            'exp' => time() + 28800,
        ], $this->secret());
    }

    public function publicUrl(): string
    {
        return rtrim((string) (getenv('MERCURE_PUBLIC_URL') ?: 'https://hse.aria-ai.ir/.well-known/mercure'), '/');
    }

    private function hub(): string
    {
        return rtrim((string) (getenv('MERCURE_URL') ?: ''), '/');
    }

    private function secret(): string
    {
        return (string) (getenv('MERCURE_JWT_SECRET') ?: '');
    }
}

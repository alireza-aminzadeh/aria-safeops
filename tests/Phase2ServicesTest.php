<?php

namespace App\Tests;

use App\Domain\Psm\Api754KpiCalculator;
use App\Infrastructure\AiGateway\HseKnowledgePack;
use App\Infrastructure\Mercure\Hs256Jwt;
use PHPUnit\Framework\TestCase;

final class Phase2ServicesTest extends TestCase
{
    public function testApi754Tiers(): void
    {
        $this->assertSame(1, Api754KpiCalculator::tier('injury', 'critical'));
        $this->assertSame(2, Api754KpiCalculator::tier('incident', 'high'));
        $this->assertSame(3, Api754KpiCalculator::tier('near_miss', 'high'));
        $this->assertSame(4, Api754KpiCalculator::tier('near_miss', 'low'));
        $counts = Api754KpiCalculator::counts([
            ['type' => 'injury', 'severity' => 'critical'],
            ['type' => 'incident', 'severity' => 'high'],
            ['type' => 'near_miss', 'severity' => 'low'],
        ]);
        $this->assertSame(1, $counts['tier1']);
        $this->assertSame(1, $counts['tier2']);
        $this->assertSame(1, $counts['tier4']);
    }

    public function testKnowledgePackFindsApi754(): void
    {
        $docs = HseKnowledgePack::retrieve('API 754 شاخص ایمنی فرآیند');
        $this->assertNotEmpty($docs);
        $this->assertSame('api-754', $docs[0]['id']);
    }

    public function testHs256JwtRoundTripShape(): void
    {
        $token = Hs256Jwt::encode(['mercure' => ['publish' => ['*']]], 'test-secret');
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
        $header = json_decode(self::b64($parts[0]), true);
        $this->assertSame('HS256', $header['alg']);
    }

    private static function b64(string $value): string
    {
        $pad = strlen($value) % 4;
        if ($pad > 0) {
            $value .= str_repeat('=', 4 - $pad);
        }

        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }
}

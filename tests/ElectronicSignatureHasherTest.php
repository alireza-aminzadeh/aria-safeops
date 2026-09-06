<?php

namespace App\Tests;

use App\Domain\Shared\ElectronicSignatureHasher;
use PHPUnit\Framework\TestCase;

final class ElectronicSignatureHasherTest extends TestCase
{
    public function testHashIsDeterministic(): void
    {
        $a = ElectronicSignatureHasher::hash('permit', 'p1', 'approve', 'u1', 'علیرضا', '2026-09-06T00:00:00+00:00', str_repeat('0', 64));
        $b = ElectronicSignatureHasher::hash('permit', 'p1', 'approve', 'u1', 'علیرضا', '2026-09-06T00:00:00+00:00', str_repeat('0', 64));
        $this->assertSame($a, $b);
        $this->assertSame(64, strlen($a));
    }

    public function testHashChangesWithLinkedAuditHash(): void
    {
        $a = ElectronicSignatureHasher::hash('permit', 'p1', 'approve', 'u1', 'علیرضا', '2026-09-06T00:00:00+00:00', str_repeat('0', 64));
        $b = ElectronicSignatureHasher::hash('permit', 'p1', 'approve', 'u1', 'علیرضا', '2026-09-06T00:00:00+00:00', str_repeat('1', 64));
        $this->assertNotSame($a, $b);
    }

    public function testHashChangesWithSignerName(): void
    {
        $a = ElectronicSignatureHasher::hash('permit', 'p1', 'approve', 'u1', 'علیرضا', '2026-09-06T00:00:00+00:00', str_repeat('0', 64));
        $b = ElectronicSignatureHasher::hash('permit', 'p1', 'approve', 'u1', 'شخص دیگر', '2026-09-06T00:00:00+00:00', str_repeat('0', 64));
        $this->assertNotSame($a, $b);
    }
}

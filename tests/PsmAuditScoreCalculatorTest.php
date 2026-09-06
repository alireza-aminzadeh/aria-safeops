<?php

namespace App\Tests;

use App\Domain\Psm\PsmAuditScoreCalculator;
use App\Domain\Psm\PsmElements;
use PHPUnit\Framework\TestCase;

final class PsmAuditScoreCalculatorTest extends TestCase
{
    public function testHasFourteenElements(): void
    {
        $this->assertCount(14, PsmElements::all());
        $this->assertSame(14, PsmElements::count());
        $codes = array_column(PsmElements::all(), 'code');
        $this->assertCount(14, array_unique($codes));
    }

    public function testOverallPercentExcludesNotApplicable(): void
    {
        $ratings = ['compliant', 'compliant', 'partial', 'non_compliant', 'not_applicable'];
        // (100 + 100 + 50 + 0) / 4 = 62.5 — not_applicable حذف می‌شود
        $this->assertSame(62.5, PsmAuditScoreCalculator::overallPercent($ratings));
    }

    public function testAllNotApplicableReturnsNull(): void
    {
        $this->assertNull(PsmAuditScoreCalculator::overallPercent(['not_applicable', 'not_applicable']));
    }

    public function testFullyCompliantIsHundred(): void
    {
        $this->assertSame(100.0, PsmAuditScoreCalculator::overallPercent(['compliant', 'compliant']));
    }
}

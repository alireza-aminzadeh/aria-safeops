<?php

namespace App\Tests;

use App\Domain\Incident\SafetyKpiCalculator;
use PHPUnit\Framework\TestCase;

final class SafetyKpiCalculatorTest extends TestCase
{
    public function testLtifrKnownExample(): void
    {
        // ۳ آسیب با ازدست‌رفتن روز کار در ۵۰۰٬۰۰۰ ساعت‌کار → LTIFR = ۶
        $this->assertSame(6.0, SafetyKpiCalculator::ltifr(3, 500_000));
    }

    public function testTrirKnownExample(): void
    {
        // ۲ رویداد ثبت‌شدنی در ۲۰۰٬۰۰۰ ساعت‌کار → TRIR = ۲
        $this->assertSame(2.0, SafetyKpiCalculator::trir(2, 200_000));
    }

    public function testZeroHoursNeverDivByZero(): void
    {
        $this->assertSame(0.0, SafetyKpiCalculator::ltifr(5, 0));
        $this->assertSame(0.0, SafetyKpiCalculator::trir(5, 0));
    }

    public function testRecordableDefaults(): void
    {
        $this->assertTrue(SafetyKpiCalculator::isRecordableByDefault('injury', 'low'));
        $this->assertTrue(SafetyKpiCalculator::isRecordableByDefault('incident', 'high'));
        $this->assertFalse(SafetyKpiCalculator::isRecordableByDefault('incident', 'low'));
        $this->assertFalse(SafetyKpiCalculator::isRecordableByDefault('near_miss', 'critical'));
    }
}

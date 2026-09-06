<?php

namespace App\Tests;

use App\Domain\Emergency\EffluentCompliancePolicy;
use PHPUnit\Framework\TestCase;

final class EffluentCompliancePolicyTest extends TestCase
{
    public function testNoLimitIsAlwaysCompliant(): void
    {
        $this->assertTrue(EffluentCompliancePolicy::isCompliant('COD', 999.0, null));
    }

    public function testBelowLimitIsCompliant(): void
    {
        $this->assertTrue(EffluentCompliancePolicy::isCompliant('BOD', 25.0, 30.0));
    }

    public function testAboveLimitIsNonCompliant(): void
    {
        $this->assertFalse(EffluentCompliancePolicy::isCompliant('BOD', 45.0, 30.0));
    }

    public function testPhUsesRangeNotUpperBoundOnly(): void
    {
        $this->assertTrue(EffluentCompliancePolicy::isCompliant('pH', 7.2, 9.0));
        $this->assertFalse(EffluentCompliancePolicy::isCompliant('pH', 5.0, 9.0));
        $this->assertFalse(EffluentCompliancePolicy::isCompliant('pH', 10.0, 9.0));
    }

    public function testReferenceLimitsCoverCommonParameters(): void
    {
        $limits = EffluentCompliancePolicy::referenceLimits();
        $this->assertArrayHasKey('BOD', $limits);
        $this->assertArrayHasKey('COD', $limits);
        $this->assertArrayHasKey('pH', $limits);
    }
}

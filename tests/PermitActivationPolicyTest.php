<?php

namespace App\Tests;

use App\Domain\Permit\Entity\GasTestReading;
use App\Domain\Permit\Entity\Permit;
use App\Domain\Permit\Entity\PermitType;
use App\Domain\Permit\GasTestPolicy;
use App\Domain\Permit\IsolationPolicy;
use App\Domain\Shared\Entity\Tenant;
use App\Domain\Shared\Entity\User;
use PHPUnit\Framework\TestCase;

final class PermitActivationPolicyTest extends TestCase
{
    private function hotWorkPermit(): Permit
    {
        $permit = new Permit();
        $permit->setEquipmentTag('P-101');
        $permit->setPermitType(new PermitType('hot_work', 'کار گرم', 'Hot Work', true, true));

        return $permit;
    }

    public function testGasTestRequiredBeforeActivation(): void
    {
        $permit = $this->hotWorkPermit();
        $this->assertNotNull(GasTestPolicy::activationBlocker($permit));
        $permit->addGasTestReading(new GasTestReading($permit, 'O2', '20.900'));
        $permit->addGasTestReading(new GasTestReading($permit, 'LEL', '0.000'));
        $permit->addGasTestReading(new GasTestReading($permit, 'H2S', '0.000'));
        $this->assertNull(GasTestPolicy::activationBlocker($permit));
    }

    public function testUnsafeOxygenBlocksActivation(): void
    {
        $permit = $this->hotWorkPermit();
        $permit->addGasTestReading(new GasTestReading($permit, 'O2', '18.000'));
        $permit->addGasTestReading(new GasTestReading($permit, 'LEL', '0.000'));
        $permit->addGasTestReading(new GasTestReading($permit, 'H2S', '0.000'));
        $this->assertSame('اکسیژن باید بین ۱۹٫۵٪ و ۲۳٫۵٪ باشد.', GasTestPolicy::activationBlocker($permit));
    }

    public function testIsolationRequiredForLotoPermits(): void
    {
        $permit = $this->hotWorkPermit();
        $this->assertNotNull(IsolationPolicy::activationBlocker($permit));
        $cold = new Permit();
        $cold->setPermitType(new PermitType('cold_work', 'کار سرد', 'Cold Work', false, false));
        $this->assertNull(IsolationPolicy::activationBlocker($cold));
    }

    public function testConfirmedIsolationClearsActivationBlocker(): void
    {
        $permit = $this->hotWorkPermit();
        $user = new User(new Tenant('t'), 'op@test.local', 'اپراتور', ['ROLE_OPERATOR'], 'hash');
        $permit->confirmIsolation(['فلنج ورودی P-101'], $user);
        $this->assertNull(IsolationPolicy::activationBlocker($permit));
    }

    public function testInvalidGasTypeRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        GasTestPolicy::assertType('NH3');
    }
}

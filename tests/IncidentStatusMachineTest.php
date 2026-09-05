<?php

namespace App\Tests;

use App\Domain\Incident\IncidentStatusMachine;
use PHPUnit\Framework\TestCase;

final class IncidentStatusMachineTest extends TestCase
{
    public function testHappyPath(): void
    {
        $this->assertTrue(IncidentStatusMachine::can('reported', 'under_investigation'));
        $this->assertTrue(IncidentStatusMachine::can('under_investigation', 'capa_assigned'));
        $this->assertTrue(IncidentStatusMachine::can('capa_assigned', 'closed'));
        $this->assertFalse(IncidentStatusMachine::can('reported', 'closed'));
        $this->assertFalse(IncidentStatusMachine::can('closed', 'reported'));
    }

    public function testCapaMovesStatus(): void
    {
        $this->assertSame('capa_assigned', IncidentStatusMachine::afterCapaAdded('reported'));
        $this->assertSame('capa_assigned', IncidentStatusMachine::afterCapaAdded('under_investigation'));
        $this->assertSame('closed', IncidentStatusMachine::afterCapaAdded('closed'));
    }
}

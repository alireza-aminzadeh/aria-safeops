<?php

namespace App\Tests;

use App\Domain\Moc\Entity\MocRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;

final class MocWorkflowTest extends TestCase
{
    private function machine(): StateMachine
    {
        $builder = new DefinitionBuilder();
        $builder->addPlaces(['proposed', 'risk_assessment', 'approval', 'implementation', 'pssr', 'closed', 'rejected']);
        $builder->addTransition(new Transition('start_risk_assessment', 'proposed', 'risk_assessment'));
        $builder->addTransition(new Transition('submit_for_approval', 'risk_assessment', 'approval'));
        $builder->addTransition(new Transition('approve', 'approval', 'implementation'));
        $builder->addTransition(new Transition('reject', 'approval', 'rejected'));
        $builder->addTransition(new Transition('start_pssr', 'implementation', 'pssr'));
        $builder->addTransition(new Transition('close', 'pssr', 'closed'));
        $definition = $builder->build();

        return new StateMachine($definition, new MethodMarkingStore(true, 'status'), null, 'moc_workflow');
    }

    public function testCannotCloseWithoutPssr(): void
    {
        $moc = new MocRequest();
        $moc->setDescription('تغییر مسیر بخار');
        $workflow = $this->machine();
        $workflow->apply($moc, 'start_risk_assessment');
        $workflow->apply($moc, 'submit_for_approval');
        $workflow->apply($moc, 'approve');
        $this->assertFalse($workflow->can($moc, 'close'));
        $this->assertSame('implementation', $moc->getStatus());
        $workflow->apply($moc, 'start_pssr');
        $this->assertTrue($workflow->can($moc, 'close'));
        $workflow->apply($moc, 'close');
        $this->assertSame('closed', $moc->getStatus());
        $this->assertNotNull($moc->getPssrCompletedAt());
    }
}

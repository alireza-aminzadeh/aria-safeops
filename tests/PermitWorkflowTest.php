<?php

namespace App\Tests;

use App\Domain\Permit\Entity\Permit;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;

final class PermitWorkflowTest extends TestCase
{
    private function machine(): StateMachine
    {
        $builder = new DefinitionBuilder();
        $builder->addPlaces(['draft', 'submitted', 'hse_review', 'approved', 'active', 'suspended', 'closed', 'cancelled', 'rejected']);
        $builder->addTransition(new Transition('submit', 'draft', 'submitted'));
        $builder->addTransition(new Transition('start_review', 'submitted', 'hse_review'));
        $builder->addTransition(new Transition('request_changes', 'hse_review', 'draft'));
        $builder->addTransition(new Transition('approve', 'hse_review', 'approved'));
        $builder->addTransition(new Transition('reject', 'hse_review', 'rejected'));
        $builder->addTransition(new Transition('activate', 'approved', 'active'));
        $builder->addTransition(new Transition('suspend', 'active', 'suspended'));
        $builder->addTransition(new Transition('resume', 'suspended', 'active'));
        $builder->addTransition(new Transition('close', 'active', 'closed'));
        $builder->addTransition(new Transition('close', 'suspended', 'closed'));
        $builder->addTransition(new Transition('cancel', 'draft', 'cancelled'));
        $builder->addTransition(new Transition('cancel', 'submitted', 'cancelled'));
        $definition = $builder->build();

        return new StateMachine($definition, new MethodMarkingStore(true, 'status'), null, 'permit_to_work');
    }

    public function testHappyPathDraftToClosed(): void
    {
        $permit = new Permit();
        $workflow = $this->machine();
        $this->assertTrue($workflow->can($permit, 'submit'));
        $workflow->apply($permit, 'submit');
        $workflow->apply($permit, 'start_review');
        $workflow->apply($permit, 'approve');
        $workflow->apply($permit, 'activate');
        $this->assertSame('active', $permit->getStatus());
        $workflow->apply($permit, 'close');
        $this->assertSame('closed', $permit->getStatus());
    }

    public function testCloseFromSuspended(): void
    {
        $permit = new Permit();
        $workflow = $this->machine();
        $workflow->apply($permit, 'submit');
        $workflow->apply($permit, 'start_review');
        $workflow->apply($permit, 'approve');
        $workflow->apply($permit, 'activate');
        $workflow->apply($permit, 'suspend');
        $this->assertTrue($workflow->can($permit, 'close'));
        $workflow->apply($permit, 'close');
        $this->assertSame('closed', $permit->getStatus());
    }

    public function testRejectAndCancel(): void
    {
        $rejected = new Permit();
        $workflow = $this->machine();
        $workflow->apply($rejected, 'submit');
        $workflow->apply($rejected, 'start_review');
        $workflow->apply($rejected, 'reject');
        $this->assertSame('rejected', $rejected->getStatus());
        $this->assertFalse($workflow->can($rejected, 'activate'));

        $cancelled = new Permit();
        $workflow->apply($cancelled, 'cancel');
        $this->assertSame('cancelled', $cancelled->getStatus());
    }
}

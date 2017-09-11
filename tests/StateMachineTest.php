<?php

namespace Tests;

use Symfony\Component\EventDispatcher\EventDispatcher;

class StateMachineTest extends TestCase
{
    /** @test **/
    public function itCanHaveASetOfStates()
    {
        $this->assertEquals(['closed', 'open'], $this->getStateMachine()->getStates());
    }

    /** @test */
    public function itCanAddTransitions()
    {
        $stateMachine = $this->getStateMachine();

        $this->assertCount(0, $stateMachine->getTransitions());

        $stateMachine->addTransition('open')->from('closed')->to('open');
        $stateMachine->addTransition('close')->from('open')->to('closed');

        $this->assertCount(2, $stateMachine->getTransitions());
    }

    /** @test */
    public function transitionsAreCorrectlyAllowedOrNot()
    {
        $stateMachine = $this->getStateMachine();

        $this->assertCount(0, $stateMachine->getTransitions());

        $stateMachine->addTransition('open')->from('closed')->to('open');
        $stateMachine->addTransition('close')->from('open')->to('closed');

        $this->assertTrue($stateMachine->can('open'));
        $this->assertFalse($stateMachine->can('closed'));
    }

    /** @test */
    public function itWillApplyATransition()
    {
        $data = [];
        $stateMachine = $this->getStateMachine();
        $stateMachine->addTransition('open')->from('closed')->to('open')->calls(function () use (&$data) {
                $data[] = 'Opening';
            });
        $stateMachine->transition('open');

        $this->assertEquals(['Opening'], $data);
    }

    /** @test */
    public function itWillApplyATransitionToAModelInACallback()
    {
        $stateMachine = $this->getStateMachineWithModel();
        $stateMachine->addTransition('open')->from('closed')->to('open')->calls(function ($model) {
                $model->record = 'Opening up';
            });
        $stateMachine->transition('open');

        $this->assertEquals('Opening up', $stateMachine->getModel()->record);
    }

    /** @test */
    public function itWillApplyATransitionToAModelDirectly()
    {
        $stateMachine = $this->getStateMachineWithModel();
        $stateMachine->addTransition('open')->from('closed')->to('open');
        $stateMachine->transition('open');

        $this->assertEquals('Opening', $stateMachine->getModel()->record);
    }

    /** @test **/
    public function itWillFireEvents()
    {
        $stateMachine = $this->getStateMachineWithModel()->setEventDispatcher($bus = new EventDispatcher);
        $stateMachine->addTransition('open')->from('closed')->to('open');
        $heard = [];

        $bus->addListener('state_machine.before', function ($event) use (&$heard) {
            $heard[] = 'state_machine.before.' . $event->transition->name;
        });

        $bus->addListener('state_machine.after', function ($event) use (&$heard) {
            $heard[] = 'state_machine.after.' . $event->transition->name;
        });

        $stateMachine->transition('open');

        $this->assertEquals([
            'state_machine.before.open',
            'state_machine.after.open',
        ], $heard);
    }
}

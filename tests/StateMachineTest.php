<?php

namespace Tests;

use Konsulting\StateMachine\Exceptions\TransitionFailed;
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

        $this->assertCount(0, $stateMachine->getTransitionBag());

        $stateMachine->addTransition('open')->from('closed')->to('open');
        $stateMachine->addTransition('close')->from('open')->to('closed');

        $this->assertCount(2, $stateMachine->getTransitionBag());
    }

    /** @test */
    public function transitionsAreCorrectlyAllowedOrNot()
    {
        $stateMachine = $this->getStateMachine();

        $this->assertCount(0, $stateMachine->getTransitionBag());

        $stateMachine->addTransition('open')->from('closed')->to('open');
        $stateMachine->addTransition('close')->from('open')->to('closed');

        $this->assertTrue($stateMachine->can('open'));
        $this->assertFalse($stateMachine->can('closed'));

        $this->assertTrue($stateMachine->canTransitionTo('open'));
        $this->assertFalse($stateMachine->canTransitionTo('closed'));
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

    /** @test **/
    public function itWillRunACallback()
    {
        $state = 'I am closed';
        $stateMachine = $this->getStateMachine();
        $stateMachine->addTransition('open')->from('closed')->to('open');
        $stateMachine->addTransition('close')->from('open')->to('closed');

        $stateMachine->transition('open', function () use (&$state) {
            $state = 'I am open';
        });

        $this->assertEquals('I am open', $state);
    }

    /** @test * */
    public function aFailureDuringTransitionWillReturnThrowATransitionFailedException()
    {
        $this->expectException(TransitionFailed::class);

        $stateMachine = $this->getStateMachine();
        $stateMachine->addTransition('open')->from('closed')->to('open');
        $stateMachine->addTransition('close')->from('open')->to('closed');

        $stateMachine->transition('open', function () {
            throw new \Exception;
        });
    }

    /** @test * */
    public function itWillRunAFailedCallback()
    {
        $state = 'I am closed';
        $exception = null;

        $stateMachine = $this->getStateMachine();
        $stateMachine->addTransition('open')->from('closed')->to('open');
        $stateMachine->addTransition('close')->from('open')->to('closed');

        $stateMachine->transition('open', function () use (&$state) {
            throw new \Exception;
        }, function ($e) use (&$state, &$exception) {
            $state = 'I got stuck';
        });

        $this->assertEquals('I got stuck', $state);
    }

    /** @test **/
    public function itWillTransitionToAStateIfPossible()
    {
        $stateMachine = $this->getStateMachine();
        $stateMachine->addTransition('open')->from('closed')->to('open');

        $stateMachine->transitionTo('open');

        $this->assertEquals('open', $stateMachine->getCurrentState());
    }

    /** @test * */
    public function itWillNotTransitionToAStateIfNotPossible()
    {
        $this->expectException(TransitionFailed::class);

        $stateMachine = $this->getStateMachine();
        $stateMachine->addTransition('open')->from('closed')->to('open');

        $stateMachine->transitionTo('closed');
    }
}

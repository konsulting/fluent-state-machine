<?php

namespace Tests;

use Konsulting\StateMachine\Exceptions\StateMachineException;
use Konsulting\StateMachine\StateMachine;
use Konsulting\StateMachine\Transition;
use Konsulting\StateMachine\TransitionBag;
use Konsulting\StateMachine\TransitionFactory;
use Konsulting\StateMachine\Transitions;

class TransitionFactoryTest extends TestCase
{
    /** @test * */
    public function itWillMakeATransitionFluently()
    {
        $factory = $this->getFactory();
        $transition = $factory->make('open')->from('open')->to('closed');

        $this->assertInstanceOf(Transition::class, $transition);
        $this->assertEquals([
            'name'  => 'open',
            'from'  => 'open',
            'to'    => 'closed',
            'calls' => null,
            'guard' => null,
        ], $transition->describe());
    }

    /** @test * */
    public function itWillMakeATransitionDeclaratively()
    {
        $factory = $this->getFactory();
        $transition = $factory->make('open', 'open', 'closed');

        $this->assertInstanceOf(Transition::class, $transition);
        $this->assertEquals([
            'name'  => 'open',
            'from'  => 'open',
            'to'    => 'closed',
            'calls' => null,
            'guard' => null,
        ], $transition->describe());
    }

    /** @test * */
    public function itWontMakeATransitionWithoutAStateMachine()
    {
        $this->expectException(StateMachineException::class);

        $factory = new TransitionFactory();
        $factory->make('open');
    }

    protected function getFactory()
    {
        $transitions = new TransitionBag($factory = new TransitionFactory);
        new StateMachine(['closed', 'open'], $transitions);

        // $transitions StateMachine is set during the $stateMachine instantiation.

        return $factory;
    }
}

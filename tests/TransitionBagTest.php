<?php

namespace Tests;

use Konsulting\StateMachine\Exceptions\DuplicateTransitionRoute;
use Konsulting\StateMachine\Transition;
use Konsulting\StateMachine\TransitionBag;

class TransitionBagTest extends TestCase
{
    /** @test */
    public function itCanAddATransition()
    {
        $stateMachine = $this->getStateMachine();
        $transitions = (new TransitionBag)->setStateMachine($stateMachine);

        $this->assertCount(0, $transitions);

        $transitions->push(new Transition($stateMachine, 'open'));

        $this->assertCount(1, $transitions);
    }

    /** @test */
    public function itWillFindATransitionByNameOrRoute()
    {
        $stateMachine = $this->getStateMachine();
        $transitions = (new TransitionBag)->setStateMachine($stateMachine);

        $open = (new Transition($stateMachine, 'open'))->from('closed')->to('open');
        $close = (new Transition($stateMachine, 'close'))->from('open')->to('closed');

        $transitions->push($open)->push($close);

        $this->assertEquals($close, $transitions->findByName('close'));
        $this->assertEquals($open, $transitions->findByRoute('closed', 'open'));
    }

    /** @test */
    public function itWillFindAnAvailableTransitionByName()
    {
        $stateMachine = $this->getStateMachine()->setCurrentState('open');
        $transitions = (new TransitionBag)->setStateMachine($stateMachine);

        $open = (new Transition($stateMachine, 'open'))->from('closed')->to('open');
        $stayOpen = (new Transition($stateMachine, 'open'))->from('open')->to('open');
        $close = (new Transition($stateMachine, 'close'))->from('open')->to('closed');

        $transitions->push($open)->push($stayOpen)->push($close);

        $this->assertEquals($stayOpen, $transitions->findAvailableByName('open'));
    }

    /** @test */
    public function itWillAcceptTransitionsWithDuplicateNames()
    {
        $stateMachine = $this->getStateMachine();
        $transitions = (new TransitionBag)->setStateMachine($stateMachine);

        $transitions->push((new Transition($stateMachine, 'open'))->from('closed'));
        $transitions->push((new Transition($stateMachine, 'open')));

        $this->assertCount(2, $transitions);
    }

    /** @test */
    public function itWontAcceptTransitionsWithDuplicateRoutes()
    {
        $this->expectException(DuplicateTransitionRoute::class);

        $stateMachine = $this->getStateMachine();
        $transitions = (new TransitionBag)->setStateMachine($stateMachine);

        $transitions->push(new Transition($stateMachine, 'open'));
        $transitions->push(new Transition($stateMachine, 'close'));
    }
}

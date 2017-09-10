<?php

namespace Tests;

use Konsulting\StateMachine\Exceptions\DuplicateTransitionName;
use Konsulting\StateMachine\Exceptions\DuplicateTransitionRoute;
use Konsulting\StateMachine\Transition;
use Konsulting\StateMachine\Transitions;

class TransitionsTest extends TestCase
{
    /** @test **/
    public function itCanAddATransition()
    {
        $stateMachine = $this->getStateMachine();
        $transitions = new Transitions;

        $this->assertCount(0, $transitions);

        $transitions->push(new Transition($stateMachine, 'open'));

        $this->assertCount(1, $transitions);
    }

    /** @test **/
    public function itWillFindATransitionByNameOrRoute()
    {
        $stateMachine = $this->getStateMachine();
        $transitions = new Transitions;

        $open = (new Transition($stateMachine, 'open'))->from('closed')->to('open');
        $close = (new Transition($stateMachine, 'close'))->from('open')->to('closed');

        $transitions->push($open)->push($close);

        $this->assertEquals($close, $transitions->findByName('close'));
        $this->assertEquals($open, $transitions->findByRoute('closed', 'open'));
    }

    /** @test **/
    public function itWontAcceptTransitionsWithDuplicateNames()
    {
        $this->expectException(DuplicateTransitionName::class);

        $stateMachine = $this->getStateMachine();
        $transitions = new Transitions;

        $transitions->push(new Transition($stateMachine, 'open'));
        $transitions->push(new Transition($stateMachine, 'open'));
    }

    /** @test * */
    public function itWontAcceptTransitionsWithDuplicateRoutes()
    {
        $this->expectException(DuplicateTransitionRoute::class);

        $stateMachine = $this->getStateMachine();
        $transitions = new Transitions;

        $transitions->push(new Transition($stateMachine, 'open'));
        $transitions->push(new Transition($stateMachine, 'close'));
    }
}

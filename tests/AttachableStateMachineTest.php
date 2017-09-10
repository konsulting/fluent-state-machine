<?php

namespace Tests;

class AttachableStateMachineTest extends TestCase
{
    /** @test **/
    public function itWillBuildAStateMachineAsDefined()
    {
        $stateMachine = new Helpers\AttachedStateMachine(new Helpers\TestModel);

        $this->assertCount(2, $stateMachine->getTransitions());
        $this->assertEquals('closed', $stateMachine->getCurrentState());
        $stateMachine->apply('open');
        $this->assertEquals('open', $stateMachine->getCurrentState());
    }

    /** @test **/
    public function doorTest()
    {
        $door = new Helpers\Door('closed');
        $door->transitionTo('open');

        // would rather $door->open
        // perhaps remove default calls setup, maybe the function should call to the SM transition
        // could pass through a callback to complete, or we could check can and only set when completed.

        $this->assertEquals('open', $door->state);
    }
}

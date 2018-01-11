<?php

namespace Tests;

use Konsulting\StateMachine\Exceptions\TransitionFailed;

class AttachableStateMachineTest extends TestCase
{
    /** @test */
    public function itWillBuildAStateMachineAsDefined()
    {
        $stateMachine = new Stubs\AttachedStateMachine(new Stubs\TestModel);

        $this->assertCount(2, $stateMachine->getTransitionBag());
        $this->assertEquals('closed', $stateMachine->getCurrentState());
        $stateMachine->transition('open');
        $this->assertEquals('open', $stateMachine->getCurrentState());
    }

    /** @test */
    public function doorCanOpen()
    {
        $door = new Stubs\Door('closed');
        $door->open();

        $this->assertEquals('open', $door->state);
    }

    /** @test */
    public function doorCannotClose()
    {
        $this->expectException(TransitionFailed::class);

        $door = new Stubs\Door('closed');
        $door->close();
    }
}

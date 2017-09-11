<?php

namespace Tests;

use Konsulting\StateMachine\Exceptions\TransitionNotAvailable;

class AttachableStateMachineTest extends TestCase
{
    /** @test **/
    public function itWillBuildAStateMachineAsDefined()
    {
        $stateMachine = new Helpers\AttachedStateMachine(new Helpers\TestModel);

        $this->assertCount(2, $stateMachine->getTransitions());
        $this->assertEquals('closed', $stateMachine->getCurrentState());
        $stateMachine->transition('open');
        $this->assertEquals('open', $stateMachine->getCurrentState());
    }

    /** @test **/
    public function doorCanOpen()
    {
        $door = new Helpers\Door('closed');
        $door->open();

        $this->assertEquals('open', $door->state);
    }

    /** @test * */
    public function doorCannotClose()
    {
        $this->expectException(TransitionNotAvailable::class);

        $door = new Helpers\Door('closed');
        $door->close();
    }
}

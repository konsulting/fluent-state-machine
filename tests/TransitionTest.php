<?php

namespace Tests;

use Konsulting\StateMachine\Exceptions\NoModelAvailableForMethod;
use Konsulting\StateMachine\Exceptions\TransitionNotNamed;
use Konsulting\StateMachine\Transition;

class TransitionTest extends TestCase
{
    /** @test **/
    public function itMustHaveAName()
    {
        $this->expectException(TransitionNotNamed::class);

        new Transition($this->getStateMachine(), '');
    }

    /** @test **/
    public function itCanBeBuiltFluently()
    {
        $transition = Transition::fluent($this->getStateMachine(), 'open')
            ->from('closed')
            ->to('open')
            ->calls(function () {
                return "Opening";
            });

        $this->assertArraySubset([
            'name' => 'open',
            'from' => 'closed',
            'to' => 'open',
        ], $transition->describe());

        $this->assertEquals('Opening', $transition->describe()['calls']());
    }

    /** @test * */
    public function itCanBeBuiltDeclaratively()
    {
        $transition = Transition::declare($this->getStateMachine(), [
            'name' => 'open',
            'from' => 'closed',
            'to' => 'open',
            'calls' => function () {
                return "Opening";
            }
        ]);

        $this->assertArraySubset([
            'name' => 'open',
            'from' => 'closed',
            'to' => 'open',
        ], $transition->describe());

        $this->assertEquals('Opening', $transition->describe()['calls']());
    }

    /** @test * */
    public function itWontBuildACallableFromAStringWhenAModelIsAbsent()
    {
        $this->expectException(NoModelAvailableForMethod::class);

        Transition::fluent($this->getStateMachine(), 'open')
            ->from('closed')
            ->to('open')
            ->calls('open-door');
    }

    /** @test **/
    public function itWillBuildACallableFromAStringWhenAModelIsPresent()
    {
        $transition = Transition::fluent($this->getStateMachine()->setModel(new Helpers\TestModel), 'open')
            ->from('closed')
            ->to('open')
            ->calls('open-door');

        $this->assertEquals('Opening Door', $transition->describe()['calls']());
    }

    /** @test * */
    public function itWillBuildACallableFromTheTransitionNameAModelIsPresent()
    {
        $transition = Transition::fluent($this->getStateMachine()->setModel(new Helpers\TestModel), 'open')
            ->from('closed')
            ->to('open');

        $this->assertEquals('Opening', $transition->describe()['calls']());
    }
}

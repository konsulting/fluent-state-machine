<?php

namespace Tests;

use Konsulting\StateMachine\Exceptions\NoModelAvailableForMethod;
use Konsulting\StateMachine\Exceptions\TransitionFailed;
use Konsulting\StateMachine\Exceptions\TransitionGuardFailed;
use Konsulting\StateMachine\Exceptions\TransitionNotNamed;
use Konsulting\StateMachine\Transition;
use Tests\Stubs\TestModel;

class TransitionTest extends TestCase
{
    /** @test */
    public function itMustHaveAName()
    {
        $this->expectException(TransitionNotNamed::class);

        new Transition($this->getStateMachine(), '');
    }

    /** @test */
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
            'to'   => 'open',
        ], $transition->describe());

        $this->assertEquals('Opening', $transition->describe()['calls']());
    }

    /** @test */
    public function itCanBeBuiltDeclaratively()
    {
        $transition = Transition::declare($this->getStateMachine(), [
            'name'  => 'open',
            'from'  => 'closed',
            'to'    => 'open',
            'calls' => function () {
                return "Opening";
            }
        ]);

        $this->assertArraySubset([
            'name' => 'open',
            'from' => 'closed',
            'to'   => 'open',
        ], $transition->describe());

        $this->assertEquals('Opening', $transition->describe()['calls']());
    }

    /** @test */
    public function itWontBuildACallableFromAStringWhenAModelIsAbsent()
    {
        $this->expectException(NoModelAvailableForMethod::class);

        Transition::fluent($this->getStateMachine(), 'open')
            ->from('closed')
            ->to('open')
            ->calls('open-door');
    }

    /** @test */
    public function itWillBuildACallableFromAStringWhenAModelIsPresent()
    {
        $transition = Transition::fluent($this->getStateMachine()->setModel(new Stubs\TestModel), 'open')
            ->from('closed')
            ->to('open')
            ->calls('open-door');

        $this->assertEquals('Opening Door', $transition->describe()['calls']());
    }

    /** @test */
    public function itWillBuildACallableFromTheTransitionNameAModelIsPresent()
    {
        $transition = Transition::fluent($this->getStateMachine()->setModel(new Stubs\TestModel), 'open')
            ->from('closed')
            ->to('open');

        $this->assertEquals('Opening', $transition->describe()['calls']());
    }

    /** @test */
    public function aGuardClosureCanBeSet()
    {
        $transition = Transition::fluent($this->getStateMachine()->setModel(new Stubs\TestModel), 'open')
            ->from('closed')
            ->to('open')
            ->guard('phpinfo');

        $this->assertEquals('phpinfo', $transition->describe()['guard']);
    }

    /** @test */
    public function itFailsIfAGuardClosureReturnsFalse()
    {
        $transition = Transition::fluent($this->getStateMachine()->setModel(new Stubs\TestModel), 'open')
            ->from('closed')
            ->to('open')
            ->guard(function () {
                return false;
            });

        try {
            $transition->apply();
        } catch (TransitionFailed $e) {
            $this->assertInstanceOf(TransitionGuardFailed::class, $e->getPrevious());

            return;
        }

        $this->fail('Expected exception.');
    }

    /** @test */
    public function theGuardClosureReceivesTheModelAsAnArgument()
    {
        $extractModel = null;

        $transition = Transition::fluent($this->getStateMachine()->setModel(new Stubs\TestModel), 'open')
            ->from('closed')
            ->to('open')
            ->guard(function (TestModel $model) use (&$extractModel) {
                $extractModel = $model;

                return $model->open() === 'Opening';
            });

        $transition->apply();

        $this->assertInstanceOf(TestModel::class, $extractModel);
    }
}

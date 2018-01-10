<?php

namespace Konsulting\StateMachine;

use Konsulting\StateMachine\Exceptions\StateMachineException;

class TransitionFactory
{
    protected $transitionClass = Transition::class;
    protected $useDefaultCall = true;
    protected $stateMachine;

    public function __construct($transitionClass = null)
    {
        $this->transitionClass = $transitionClass ?: $this->transitionClass;
    }

    /**
     * Control whether transitions should try to make a default callable
     * using the transition name.
     */
    public function useDefaultCall($value = true)
    {
        $this->useDefaultCall = $value;

        return $this;
    }

    public function setStateMachine(StateMachine $stateMachine)
    {
        $this->stateMachine = $stateMachine;
    }

    /**
     * Construct a Transition based on the input. We can provide transitions built
     * fluently (just the name), or constructed declaratively using an array,
     * or a set of inputs (name, from, to, calls).
     *
     * @param       $name
     * @param array $arguments
     * @return Transition
     * @throws StateMachineException
     */
    public function make($name, ...$arguments)
    {
        $this->guardStateMachine();
        $arguments = array_merge([$this->stateMachine], [$name], $arguments);

        if (count($arguments) != 2 || is_array($arguments[1])) {
            return $this->prepare(call_user_func_array([$this->transitionClass, 'declare'], $arguments));
        }

        return $this->prepare(call_user_func_array([$this->transitionClass, 'fluent'], $arguments));
    }

    /**
     * Make sure we have a StateMachine available to use when building Transitions.
     *
     * @throws StateMachineException
     */
    protected function guardStateMachine()
    {
        if (!$this->stateMachine) {
            throw new StateMachineException('No StateMachine defined, we need one to create transitions');
        }
    }

    /**
     * Routine to prepare a Transition before passing it back for use.
     *
     * @return Transition
     */
    protected function prepare(Transition $transition)
    {
        return $transition->useDefaultCall($this->useDefaultCall);
    }
}

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
     * @return Transition
     */
    public function make(...$arguments)
    {
        $this->guardStateMachine();
        $arguments = array_merge([$this->stateMachine], $arguments);

        if (count($arguments) != 2 || is_array($arguments[1])) {
            return $this->prepare(call_user_func_array([$this->transitionClass, 'declare'], $arguments));
        }

        return $this->prepare(call_user_func_array([$this->transitionClass, 'fluent'], $arguments));
    }

    protected function guardStateMachine()
    {
        if (!$this->stateMachine) {
            throw new StateMachineException('No StateMachine defined, we need one to create transitions');
        }
    }

    protected function prepare(Transition $transition)
    {
        return $transition->useDefaultCall($this->useDefaultCall);
    }
}

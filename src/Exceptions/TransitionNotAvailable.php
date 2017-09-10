<?php

namespace Konsulting\StateMachine\Exceptions;

use Konsulting\StateMachine\Transition;

class TransitionNotAvailable extends StateMachineException
{
    public $transition;

    public function __construct(Transition $transition)
    {
        parent::__construct("Transition '{$transition->name}' not available");
        $this->transition = $transition;
    }
}

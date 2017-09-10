<?php

namespace Konsulting\StateMachine\Exceptions;

class TransitionNotFound extends StateMachineException
{
    public function __construct($name)
    {
        parent::__construct("Transition '{$name} not found");
    }
}

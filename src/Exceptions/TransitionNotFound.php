<?php

namespace Konsulting\StateMachine\Exceptions;

class TransitionNotFound extends StateMachineException
{
    public function __construct($name, $currentState = null)
    {
        $message = "Transition '{$name}' not found.";

        if (is_string($currentState)) {
            $message .= " Current state is '{$currentState}'.";
        }

        parent::__construct($message);
    }
}

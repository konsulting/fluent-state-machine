<?php

namespace Konsulting\StateMachine\Exceptions;

class StateNotDefined extends StateMachineException
{
    public function __construct($state)
    {
        parent::__construct("State '{$state}' not defined");
    }
}

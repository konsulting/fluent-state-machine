<?php

namespace Konsulting\StateMachine\Exceptions;

class NoModelAvailableForMethod extends StateMachineException
{
    public function __construct($method)
    {
        parent::__construct("No model for the '$method' to attach to");
    }
}

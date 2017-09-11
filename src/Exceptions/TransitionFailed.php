<?php

namespace Konsulting\StateMachine\Exceptions;

use Exception;
use Konsulting\StateMachine\Transition;

class TransitionFailed extends StateMachineException
{
    public $transition;

    public function __construct(Transition $transition = null, Exception $exception)
    {
        parent::__construct("Transition ".($transition->name ?? '')." failed", 1, $exception);

        $this->transition = $transition;
    }
}

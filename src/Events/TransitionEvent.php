<?php

namespace Konsulting\StateMachine\Events;

use Konsulting\StateMachine\Transition;
use Symfony\Component\EventDispatcher\Event;

class TransitionEvent extends Event
{
    public $transition;

    public function __construct(Transition $transition)
    {
        $this->transition = $transition;
    }
}

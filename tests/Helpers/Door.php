<?php

namespace Tests\Helpers;

class Door
{
    public $state;

    public function __construct($state)
    {
        $this->state = $state;
        $this->stateMachine = new AttachedStateMachine($this);
    }

    public function transitionTo($state)
    {
        $this->stateMachine->apply($state);
    }

    public function open()
    {

    }

    public function close()
    {

    }
}

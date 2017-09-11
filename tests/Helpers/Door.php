<?php

namespace Tests\Helpers;

class Door
{
    public $state;
    protected $stateMachine;

    public function __construct($state)
    {
        $this->state = $state;
        $this->stateMachine = new AttachedStateMachine($this);
    }

    public function open()
    {
        $this->stateMachine->transition('open', function () {
            // echo "I am opening";
        });
    }

    public function close()
    {
        $this->stateMachine->transition('close', function () {
            // echo "I am closing";
        });
    }
}

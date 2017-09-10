<?php

namespace Konsulting\StateMachine;

abstract class AttachableStateMachine extends StateMachine
{
    public function __construct($model)
    {
        $this->transitions = new Transitions;
        $this->setModel($model);
        $this->define();
    }

    abstract protected function define();
}

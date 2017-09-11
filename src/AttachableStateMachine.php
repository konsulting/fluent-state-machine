<?php

namespace Konsulting\StateMachine;

abstract class AttachableStateMachine extends StateMachine
{
    public function __construct($model)
    {
        parent::__construct(['default']);
        $this->setModel($model);
        $this->define();
    }

    abstract protected function define();
}

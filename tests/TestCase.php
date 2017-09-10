<?php

namespace Tests;

use Konsulting\StateMachine\StateMachine;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * @return StateMachine
     */
    protected function getStateMachine()
    {
        return new StateMachine(['closed', 'open']);
    }

    /**
     * @return StateMachine
     */
    protected function getStateMachineWithModel($model = null)
    {
        $model = $model ?: new Helpers\TestModel;

        return $this->getStateMachine()->setModel($model);
    }
}

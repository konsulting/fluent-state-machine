<?php

namespace Tests\Helpers;

use Konsulting\StateMachine\AttachableStateMachine;

class AttachedStateMachine extends AttachableStateMachine
{
    protected function define()
    {
        $this->setStates(['closed', 'open'])
            ->setCurrentState($this->model->state ?? 'closed')
            ->addTransition('open')->from('closed')->to('open')
            ->addTransition('close')->from('open')->to('closed');
    }

    public function setCurrentState($state)
    {
        $this->model->state = $state;

        return parent::setCurrentState($state);
    }
}

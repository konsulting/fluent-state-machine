<?php

namespace Tests\Helpers;

use Konsulting\StateMachine\AttachableStateMachine;
use Konsulting\StateMachine\TransitionFactory;
use Konsulting\StateMachine\TransitionBag;

class AttachedStateMachine extends AttachableStateMachine
{
    protected function define()
    {
        $transitionFactory = (new TransitionFactory)->useDefaultCall(false);
        $transitions = new TransitionBag($transitionFactory);

        $this->setTransitionBag($transitions)
            ->setStates(['closed', 'open'])
            ->setCurrentState($this->model->state ?? 'closed')
            ->addTransition('open')->from('closed')->to('open')
            ->addTransition('close')->from('open')->to('closed');
    }

    public function setCurrentState($state)
    {
        if ($this->model) {
            $this->model->state = $state;
        }

        return parent::setCurrentState($state);
    }
}

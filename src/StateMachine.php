<?php

namespace Konsulting\StateMachine;

use Konsulting\StateMachine\Exceptions\StateNotDefined;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

class StateMachine
{
    protected $states;
    /**
     * @var Transitions $transitions
     */
    protected $transitions;
    protected $model;
    protected $currentState;
    /**
     * @var EventDispatcher $events
     */
    protected $events;

    public function __construct($states, Transitions $transitions = null)
    {
        $this->setTransitions($transitions);
        $this->setStates($states);
        $this->setCurrentState($states[0] ?? null);
    }

    public function setTransitions(Transitions $transitions = null)
    {
        $this->transitions = $transitions ?: new Transitions;
        $this->transitions->setStateMachine($this);

        return $this;
    }

    public function getStates()
    {
        return $this->states;
    }

    public function setStates($states = [])
    {
        $this->states = $states;

        return $this;
    }

    public function hasState($state)
    {
        return in_array($state, $this->states);
    }

    public function getCurrentState()
    {
        return $this->currentState;
    }

    public function setCurrentState($state)
    {
        $this->currentState = $this->guardState($state);

        return $this;
    }

    protected function guardState($state)
    {
        if ($this->hasState($state)) {
            return $state;
        }

        throw new StateNotDefined($state);
    }

    public function getModel()
    {
        return $this->model;
    }

    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    public function hasModel()
    {
        return ! is_null($this->model);
    }

    public function addTransition(...$arguments)
    {
        return $this->transitions->push(...$arguments)->last();
    }

    public function getTransitions()
    {
        return $this->transitions;
    }

    public function can($name)
    {
        return ($transition = $this->transitions->findByName($name))
            && $transition->isAvailable();
    }

    public function canTransitionTo($state)
    {
        return !! $this->transitions->findByRoute($this->currentState, $state);
    }

    public function transition($name, callable $callback = null, callable $failedCallback = null)
    {
        $transition = $name instanceof Transition ? $name : $this->transitions->findByName($name);

        if (! $transition) {
            throw new Exceptions\TransitionFailed(
                null,
                new Exceptions\TransitionNotFound($name)
            );
        }

        return $transition->apply($callback, $failedCallback);
    }

    public function transitionTo($state, callable $callback = null, callable $failedCallback = null)
    {
        $transition = $this->transitions->findByRoute($this->currentState, $state);

        if (!$transition) {
            throw new Exceptions\TransitionFailed(
                null,
                new Exceptions\TransitionNotFound("{$this->currentState}' to '{$state}")
            );
        }

        return $transition->apply($callback, $failedCallback);
    }

    public function getCallbackArguments()
    {
        return [$this->getModel()];
    }

    public function setEventDispatcher(EventDispatcher $dispatcher)
    {
        $this->events = $dispatcher;

        return $this;
    }

    public function dispatchEvent($name, Event $event)
    {
        if (! $this->events) {
            return;
        }

        $this->events->dispatch($name, $event);
    }

    public function tap($callback)
    {
        $callback($this);

        return $this;
    }
}

<?php

namespace Konsulting\StateMachine;

use Konsulting\StateMachine\Exceptions\StateNotDefined;
use Konsulting\StateMachine\Exceptions\TransitionFailed;
use Konsulting\StateMachine\Exceptions\TransitionNotFound;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

class StateMachine
{
    /** @var array $states */
    protected $states;

    /** @var Transitions $transitions */
    protected $transitions;

    /** @var mixed $model */
    protected $model;

    /** @var string $currentState */
    protected $currentState;

    /** @var EventDispatcher $events */
    protected $events;

    /**
     * StateMachine constructor, set initial states and optionally pass
     * through a Transitions object with a custom Transition Factory.
     * This allows us more control when constructing transitions.
     */
    public function __construct(array $states = [], Transitions $transitions = null)
    {
        $this->setTransitions($transitions);
        $this->setStates($states);
    }

    public function setTransitions(Transitions $transitions = null)
    {
        $this->transitions = $transitions ?: new Transitions;
        $this->transitions->setStateMachine($this);

        return $this;
    }

    public function addTransition($name, ...$arguments)
    {
        return $this->transitions->push($name, ...$arguments)->last();
    }

    public function getTransitions()
    {
        return $this->transitions;
    }

    /**
     * Set the states for the machine. We always reset to the default when these change.
     */
    public function setStates($states = [])
    {
        $this->states = $states;
        $this->setToDefaultState();

        return $this;
    }

    public function getStates()
    {
        return $this->states;
    }

    public function hasState($state)
    {
        return in_array($state, $this->states);
    }

    public function setCurrentState($state)
    {
        $this->currentState = $this->guardState($state);

        return $this;
    }

    public function setToDefaultState()
    {
        $this->currentState = $this->states[0] ?? null;
    }

    public function getCurrentState()
    {
        return $this->currentState;
    }

    protected function guardState($state)
    {
        if ($this->hasState($state)) {
            return $state;
        }

        throw new StateNotDefined($state);
    }

    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function hasModel()
    {
        return ! is_null($this->model);
    }

    /**
     * Check if a named transition is possible
     **
     * @return bool
     */
    public function can($name)
    {
        return ($transition = $this->transitions->findAvailableByName($name))
            && $transition->isAvailable();
    }

    /**
     * Check if it is possible to transition to a specific state
     *
     * @return bool
     */
    public function canTransitionTo($state)
    {
        return !! $this->transitions->findByRoute($this->currentState, $state);
    }

    /**
     * Try to perform a named transition. We are able to pass through callbacks
     * for use when transitioning, or if the transition fails. Any exceptions
     * that occur during transition will throw a TransitionFailed exception.
     *
     * @return mixed
     * @throws TransitionFailed
     */
    public function transition($name, callable $callback = null, callable $failedCallback = null)
    {
        $transition = $name instanceof Transition ? $name : $this->transitions->findAvailableByName($name);

        if (! $transition) {
            throw new TransitionFailed(
                null,
                new TransitionNotFound($name, $this->currentState)
            );
        }

        return $transition->apply($callback, $failedCallback);
    }

    /**
     * Try to perform a transition to a state. Callbacks can be used as per transaction().
     *
     * @return mixed
     * @throws TransitionFailed
     */
    public function transitionTo($state, callable $callback = null, callable $failedCallback = null)
    {
        $transition = $this->transitions->findByRoute($this->currentState, $state);

        if (!$transition) {
            throw new TransitionFailed(
                null,
                new TransitionNotFound("{$this->currentState}' to '{$state}")
            );
        }

        return $transition->apply($callback, $failedCallback);
    }

    /**
     * Provide the arguments to be used when running the transitions 'call'
     * callable, which can be provided during the setup phase of a
     * transition.
     */
    public function getArgumentsForCall()
    {
        return [$this->getModel()];
    }

    /**
     * If we want to listen for events, we can set a Symfony EventDispatcher.
     */
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

    /**
     * Simple way to do something with this object but still return it at the end.
     */
    public function tap($callback)
    {
        $callback($this);

        return $this;
    }
}

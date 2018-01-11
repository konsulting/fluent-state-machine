<?php

namespace Konsulting\StateMachine;

use Konsulting\StateMachine\Exceptions\StateNotDefined;
use Konsulting\StateMachine\Exceptions\TransitionFailed;
use Konsulting\StateMachine\Exceptions\TransitionNotFound;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

class StateMachine
{
    /**
     * The states that the machine may exist in.
     *
     * @var string[] $states
     */
    protected $states;

    /**
     * The current state of the machine.
     *
     * @var string $currentState
     */
    protected $currentState;

    /**
     * The container for all transitions.
     *
     * @var TransitionBag $transitionBag
     */
    protected $transitionBag;

    /**
     * The attached model.
     *
     * @var object|null $model
     */
    protected $model;


    /**
     * The event dispatcher.
     *
     * @var EventDispatcher $events
     */
    protected $events;

    /**
     * StateMachine constructor. Set initial states and optionally pass through a TransitionBag object with a custom
     * Transition Factory. This allows us more control when constructing transitions.
     *
     * @param array              $states
     * @param TransitionBag|null $transitions
     */
    public function __construct(array $states = [], TransitionBag $transitions = null)
    {
        $this->setTransitionBag($transitions);
        $this->setStates($states);
    }

    /**
     * Register the transition bag, which may contain multiple transitions.
     *
     * @param TransitionBag|null $transitions
     * @return $this
     */
    public function setTransitionBag(TransitionBag $transitions = null)
    {
        $this->transitionBag = $transitions ?: new TransitionBag;
        $this->transitionBag->setStateMachine($this);

        return $this;
    }

    /**
     * Register a transition.
     *
     * @param string|Transition $name
     * @param array             ...$arguments
     * @return Transition
     * @throws Exceptions\DuplicateTransitionRoute
     * @throws Exceptions\StateMachineException
     */
    public function addTransition($name, ...$arguments)
    {
        return $this->transitionBag->push($name, ...$arguments)->last();
    }

    /**
     * Get the transition bag, which contains all transitions for the state machine.
     *
     * @return TransitionBag
     */
    public function getTransitionBag()
    {
        return $this->transitionBag;
    }

    /**
     * Set the states for the machine. We always reset to the default when these change.
     *
     * @param array $states
     * @return $this
     */
    public function setStates($states = [])
    {
        $this->states = $states;
        $this->setToDefaultState();

        return $this;
    }

    /**
     * Get all registered states.
     *
     * @return array
     */
    public function getStates()
    {
        return $this->states;
    }

    /**
     * Check if the given state exists.
     *
     * @param string $state
     * @return bool
     */
    public function hasState($state)
    {
        return in_array($state, $this->states);
    }

    /**
     * Set the current state. The state must already exist on the state machine.
     *
     * @param string $state
     * @return $this
     * @throws StateNotDefined
     */
    public function setCurrentState($state)
    {
        $this->currentState = $this->guardState($state);

        return $this;
    }

    /**
     * Set the current state to the first registered state (or null if no states exist).
     *
     * @return null
     */
    public function setToDefaultState()
    {
        $this->currentState = $this->states[0] ?? null;
    }

    /**
     * Get the current state.
     *
     * @return string
     */
    public function getCurrentState()
    {
        return $this->currentState;
    }

    /**
     * Check if the given state exists; throw an exception if not.
     *
     * @param string $state
     * @return string
     * @throws StateNotDefined
     */
    protected function guardState($state)
    {
        if ($this->hasState($state)) {
            return $state;
        }

        throw new StateNotDefined($state);
    }

    /**
     * Set the attached model.
     *
     * @param object $model
     * @return $this
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Get the attached model.
     *
     * @return object|null
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Check if the state machine has an attached model.
     *
     * @return bool
     */
    public function hasModel()
    {
        return ! is_null($this->model);
    }

    /**
     * Check if a named transition is possible
     *
     * @param string $name
     * @return bool
     */
    public function can($name)
    {
        return ($transition = $this->transitionBag->findAvailableByName($name))
            && $transition->isAvailable();
    }

    /**
     * Check if it is possible to transition to a specific state
     *
     * @param string $state
     * @return bool
     */
    public function canTransitionTo($state)
    {
        return ! ! $this->transitionBag->findByRoute($this->currentState, $state);
    }

    /**
     * Try to perform a named transition. We are able to pass through callbacks for use when transitioning, or if the
     * transition fails. Any exceptions that occur during transition will throw a TransitionFailed exception.
     *
     * @param string|Transition $name
     * @param callable|null     $callback
     * @param callable|null     $failedCallback
     * @return mixed
     * @throws Exceptions\TransitionNotAvailable
     * @throws TransitionFailed
     */
    public function transition($name, callable $callback = null, callable $failedCallback = null)
    {
        $transition = $name instanceof Transition ? $name : $this->transitionBag->findAvailableByName($name);

        if (! $transition) {
            throw new TransitionFailed(
                null,
                new TransitionNotFound($name)
            );
        }

        return $transition->apply($callback, $failedCallback);
    }

    /**
     * Try to perform a transition to a state. Callbacks can be used as per transaction().
     *
     * @param string|Transition $state
     * @param callable|null     $callback
     * @param callable|null     $failedCallback
     * @return mixed
     * @throws Exceptions\TransitionNotAvailable
     * @throws TransitionFailed
     */
    public function transitionTo($state, callable $callback = null, callable $failedCallback = null)
    {
        $transition = $this->transitionBag->findByRoute($this->currentState, $state);

        if (! $transition) {
            throw new TransitionFailed(
                null,
                new TransitionNotFound("{$this->currentState}' to '{$state}")
            );
        }

        return $transition->apply($callback, $failedCallback);
    }

    /**
     * Provide the arguments to be used when running the transitions 'call' callable, which can be provided during the
     * setup phase of a transition.
     *
     * @return array
     */
    public function getArgumentsForCall()
    {
        return [$this->getModel()];
    }

    /**
     * Set an event dispatcher to listen for and deal with events.
     *
     * @param EventDispatcher $dispatcher
     * @return StateMachine
     */
    public function setEventDispatcher(EventDispatcher $dispatcher)
    {
        $this->events = $dispatcher;

        return $this;
    }

    /**
     * Dispatch an event.
     *
     * @param string $name
     * @param Event  $event
     * @return Event|null
     */
    public function dispatchEvent($name, Event $event)
    {
        if (! $this->events) {
            return null;
        }

        return $this->events->dispatch($name, $event);
    }

    /**
     * Simple way to do something with this object but still return it at the end.
     *
     * @param callable $callback
     * @return StateMachine
     */
    public function tap($callback)
    {
        $callback($this);

        return $this;
    }
}

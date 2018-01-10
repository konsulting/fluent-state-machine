<?php

namespace Konsulting\StateMachine;

use Konsulting\StateMachine\Exceptions\NoModelAvailableForMethod;
use Konsulting\StateMachine\Exceptions\StateNotDefined;
use Konsulting\StateMachine\Exceptions\TransitionFailed;
use Konsulting\StateMachine\Exceptions\TransitionGuardFailed;
use Konsulting\StateMachine\Exceptions\TransitionNotAvailable;
use Konsulting\StateMachine\Exceptions\TransitionNotNamed;
use Stringy\Stringy;

/**
 * @property-read string from
 * @property-read string to
 * @property-read string name
 */
class Transition
{
    /** @var StateMachine */
    protected $stateMachine;

    /**
     * The transition name.
     *
     * @var string
     */
    protected $name;

    /**
     * The destination state.
     *
     * @var string
     */
    protected $to;

    /**
     * The initial state.
     *
     * @var string
     */
    protected $from;

    /**
     * The callback to be executed during the transition.
     *
     * @var callable|null
     */
    protected $callback;

    /**
     * A closure that returns a boolean, which determines if the transition should succeed.
     *
     * @var callable|null
     */
    protected $guard;

    /**
     * @var bool
     */
    protected $useDefaultCallable = true;

    public function __construct(StateMachine $stateMachine, $name)
    {
        $this->stateMachine = $stateMachine;
        $this->name = $this->guardName($name);
    }

    /**
     * Make sure we have a name
     *
     * @return string $name
     * @throws \Konsulting\StateMachine\Exceptions\TransitionNotNamed
     */
    protected function guardName($name)
    {
        if (! empty($name)) {
            return $name;
        };

        throw new TransitionNotNamed;
    }

    /**
     * Allow us to create a transition a declarative way directly, or with an array. An array would need some or all of
     * the keys (name, from, to, calls).
     *
     * @param StateMachine         $stateMachine
     * @param string               $name
     * @param string|null          $from
     * @param string|null          $to
     * @param callable|string|null $calls
     * @return Transition
     * @throws NoModelAvailableForMethod
     * @throws StateNotDefined
     */
    public static function declare(StateMachine $stateMachine, $name, $from = null, $to = null, $calls = null)
    {
        if (is_array($name)) {
            extract($name);
        }

        return (new static($stateMachine, $name))->from($from)->to($to)->calls($calls);
    }

    /**
     * Allow us to create a transition fluently without using 'new'.
     *
     * @param StateMachine $stateMachine
     * @param string       $name
     * @return Transition
     */
    public static function fluent(StateMachine $stateMachine, $name)
    {
        return new static($stateMachine, $name);
    }

    /**
     * Set a callback to be run during the transition.
     *
     * @param string|callable $callable
     * @return Transition
     * @throws NoModelAvailableForMethod
     */
    public function calls($callable)
    {
        if ($callable) {
            $this->callback = is_callable($callable) ? $callable : $this->makeCallable($callable);
        }

        return $this;
    }

    /**
     * @param callable $callable
     * @return Transition
     * @throws NoModelAvailableForMethod
     */
    public function guard($callable)
    {
        if ($callable) {
            $this->guard = is_callable($callable) ? $callable : $this->makeCallable($callable);
        }

        return $this;
    }


    /**
     * Try to make a callable from a method name, it will try to
     * attach to the StateMachines linked model.
     *
     * @param string|callable $name
     * @return callable
     * @throws NoModelAvailableForMethod
     */
    protected function makeCallable($name)
    {
        $methodName = (string) Stringy::create($name)->camelize();

        if (! $this->stateMachine->hasModel()) {
            throw new NoModelAvailableForMethod($methodName);
        }

        return [$this->stateMachine->getModel(), $methodName];
    }

    public function to($state = null)
    {
        $this->to = $this->guardState($state);

        return $this;
    }

    /**
     * Make sure we have a valid state
     *
     * @return string
     * @throws \Konsulting\StateMachine\Exceptions\StateNotDefined
     */
    protected function guardState($state)
    {
        if ($this->stateMachine->hasState($state)) {
            return $state;
        };

        throw new StateNotDefined($state);
    }

    /**
     * Set the from state.
     *
     * @param string|null $state
     * @return $this
     * @throws StateNotDefined
     */
    public function from($state = null)
    {
        $this->from = $this->guardState($state);

        return $this;
    }

    /**
     * Help to create sets of transitions fluently by proxying back to the stateMachine for the next Transition.
     *
     * @return mixed
     */
    public function addTransition($name)
    {
        return $this->stateMachine->addTransition($name);
    }

    /**
     * Returns a simple array describing the current transition
     *
     * @return array
     * @throws NoModelAvailableForMethod
     */
    public function describe()
    {
        return [
            'name'  => $this->name,
            'from'  => $this->from,
            'to'    => $this->to,
            'calls' => $this->getTransitionCallable(),
            'guard' => $this->guard
        ];
    }

    /**
     * Get or make the callable that's attached to the transition. By default, if a callable has not been explicitly
     * set it will try to build a default callable by using the transition name and the StateMachines model. This can
     * be turned off.
     *
     * @return callable|null
     * @throws NoModelAvailableForMethod
     */
    protected function getTransitionCallable()
    {
        if (! $this->useDefaultCallable) {
            return $this->callback;
        }

        $defaultCallable = $this->stateMachine->hasModel()
            ? $this->makeCallable($this->name)
            : null;

        return $this->callback ?: $defaultCallable;
    }

    /**
     * Allow easy access to the basic details for a transition.
     */
    public function __get($name)
    {
        if (in_array($name, ['from', 'to', 'name'])) {
            return $this->{$name};
        }

        throw new \RuntimeException(__CLASS__ . "::{$name} is not accessible");
    }

    /**
     * Apply the transition.
     *
     * @param callable|null $callback
     * @param callable|null $failedCallback
     * @return mixed
     * @throws \Konsulting\StateMachine\Exceptions\TransitionFailed
     * @throws \Konsulting\StateMachine\Exceptions\TransitionNotAvailable
     */
    public function apply(callable $callback = null, callable $failedCallback = null)
    {
        try {
            if (! $this->isAvailable()) {
                throw new TransitionNotAvailable($this);
            }

            if ($this->guard && ! $this->runCallable($this->guard, $this->stateMachine->getArgumentsForCall())) {
                throw new TransitionGuardFailed;
            }

            $this->runTransition($callback);
        } catch (\Exception $e) {
            $toThrow = new TransitionFailed($this, $e);

            if ($failedCallback) {
                return $failedCallback($toThrow);
            }

            throw $toThrow;
        }
    }

    /**
     * Fire relevant events, run callbacks and update the transition state.
     *
     * @param callable|null $callback
     * @throws NoModelAvailableForMethod
     */
    protected function runTransition($callback)
    {
        $this->stateMachine->dispatchEvent('state_machine.before', new Events\TransitionEvent($this));
        $this->runCallable($this->getTransitionCallable(), $this->stateMachine->getArgumentsForCall());
        $this->runCallable($callback);
        $this->stateMachine->setCurrentState($this->to);
        $this->stateMachine->dispatchEvent('state_machine.after', new Events\TransitionEvent($this));
    }

    /**
     * Run the given callable if it's valid.
     *
     * @param callable|null $callable
     * @param array         $args
     * @return mixed
     */
    protected function runCallable($callable, $args = [])
    {
        if (is_callable($callable)) {
            return $callable(...$args);
        }
    }

    /**
     * Check if this transition is available from the current state
     */
    public function isAvailable()
    {
        return $this->stateMachine->getCurrentState() == $this->from;
    }

    /**
     * Set whether we should try to make a default callable when transitioning.
     */
    public function useDefaultCall($value = true)
    {
        $this->useDefaultCallable = ! ! $value;

        return $this;
    }
}

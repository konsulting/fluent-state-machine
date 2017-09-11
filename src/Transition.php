<?php

namespace Konsulting\StateMachine;

use Stringy\Stringy;

/**
 * @property-read string from
 * @property-read string to
 * @property-read string name
 */
class Transition
{
    protected $stateMachine;
    protected $from;
    protected $to;
    protected $callable;
    protected $name;
    protected $useDefaultCallable = true;

    public function __construct(StateMachine $stateMachine, $name)
    {
        $this->stateMachine = $stateMachine;
        $this->name = $this->guardName($name);
    }

    protected function guardName($name)
    {
        if (!empty($name)) {
            return $name;
        };

        throw new Exceptions\TransitionNotNamed;
    }

    /**
     * Allow us to create in a declarative way directly, or with an array
     */
    public static function declare(StateMachine $stateMachine, $name, $from = null, $to = null, $calls = null)
    {
        if (is_array($name)) {
            extract($name);
        }

        return (new static($stateMachine, $name))->from($from)->to($to)->calls($calls);
    }

    public static function fluent(StateMachine $stateMachine, $name)
    {
        return new static($stateMachine, $name);
    }

    public function calls($callable = null)
    {
        if ($callable) {
            $this->callable = is_callable($callable) ? $callable : $this->makeCallable($callable);
        }

        return $this;
    }

    protected function makeCallable($name)
    {
        $methodName = (string) Stringy::create($name)->camelize();

        if (! $this->stateMachine->hasModel()) {
            throw new Exceptions\NoModelAvailableForMethod($methodName);
        }

        return [$this->stateMachine->getModel(), $methodName];
    }

    public function to($state = null)
    {
        $this->to = $this->guardState($state);

        return $this;
    }

    protected function guardState($state)
    {
        if ($this->stateMachine->hasState($state)) {
            return $state;
        };

        throw new Exceptions\StateNotDefined($state);
    }

    public function from($state = null)
    {
        $this->from = $this->guardState($state);

        return $this;
    }

    public function addTransition($name)
    {
        return $this->stateMachine->addTransition($name);
    }

    public function describe()
    {
        return [
            'name' => $this->name,
            'from' => $this->from,
            'to' => $this->to,
            'calls' => $this->myCallable(),
        ];
    }

    protected function myCallable()
    {
        if (! $this->useDefaultCallable) {
            return $this->callable;
        }

        $defaultCallable = $this->stateMachine->hasModel()
            ? $this->makeCallable($this->name)
            : null;

        return $this->callable ?: $defaultCallable;
    }

    public function __get($name)
    {
        if (in_array($name, ['from', 'to', 'name'])) {
            return $this->{$name};
        }
    }

    public function apply(callable $callback = null)
    {
        if (! $this->isAvailable()) {
            throw new Exceptions\TransitionNotAvailable($this);
        }

        $this->stateMachine->dispatchEvent('state_machine.before', new Events\TransitionEvent($this));

        try {
            $callable = $this->myCallable();
            if ($callable) {
                $callable(...$this->stateMachine->getCallbackArguments());
            }
            if ($callback) {
                $callback();
            }
            $this->stateMachine->setCurrentState($this->to);
        } catch (\Exception $e) {
            throw new Exceptions\TransitionFailed($this, $e);
        }

        $this->stateMachine->dispatchEvent('state_machine.after', new Events\TransitionEvent($this));
    }

    public function isAvailable()
    {
        return $this->stateMachine->getCurrentState() == $this->from;
    }

    public function useDefaultCall($value = true)
    {
        $this->useDefaultCallable = !! $value;

        return $this;
    }
}

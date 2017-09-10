<?php

namespace Konsulting\StateMachine;

use Stringy\Stringy;

class Transition
{
    protected $stateMachine;
    protected $from;
    protected $to;
    protected $callable;
    protected $name;

    public function __construct(StateMachine $stateMachine, $name)
    {
        $this->stateMachine = $stateMachine;
        $this->name = $this->guardName($name);
    }

    protected function guardName($name)
    {
        if (! empty($name)) {
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

    public function calls($callable = null)
    {
        $this->callable = is_callable($callable) ? $callable : $this->makeCallable($callable);

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

    public static function fluent(StateMachine $stateMachine, $name)
    {
        return new static($stateMachine, $name);
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
        $defaultCallable = $this->stateMachine->hasModel()
            ? $this->makeCallable($this->name)
            : function () {};

        return $this->callable ?: $defaultCallable;
    }

    public function __get($name)
    {
        if (in_array($name, ['from', 'to', 'name'])) {
            return $this->{$name};
        }
    }

    public function apply()
    {
        if (! $this->isAvailable()) {
            throw new Exceptions\TransitionNotAvailable($this);
        }

        $this->stateMachine->dispatchEvent('state_machine.before', new Events\TransitionEvent($this));

        try {
            $this->myCallable()(...$this->stateMachine->getCallbackArguments());
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
}

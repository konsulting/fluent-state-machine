<?php

namespace Konsulting\StateMachine;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class Transitions implements Countable, IteratorAggregate
{
    /** @var array */
    protected $transitions = [];

    /** @var TransitionFactory $transitionFactory */
    protected $transitionFactory;

    public function __construct(TransitionFactory $transitionFactory = null)
    {
        $this->setTransitionFactory($transitionFactory);
    }

    public function setTransitionFactory(TransitionFactory $transitionFactory = null)
    {
        $this->transitionFactory = $transitionFactory ?: new TransitionFactory;

        return $this;
    }

    public function setStateMachine(StateMachine $stateMachine)
    {
        $this->transitionFactory->setStateMachine($stateMachine);

        return $this;
    }

    public function pushMany($transitions = [])
    {
        foreach ($transitions as $transition) {
            $this->push($transition);
        }

        return $this;
    }

    public function push($name, ...$arguments)
    {
        $transition = $name instanceof Transition
            ? $name
            : $this->transitionFactory->make($name, ...$arguments);

        $this->transitions[] = $this->guardDuplicates($transition);

        return $this;
    }

    public function last()
    {
        return end($this->transitions);
    }

    protected function guardDuplicates(Transition $transition)
    {
        if ($this->findByRoute($transition->from, $transition->to)) {
            throw new Exceptions\DuplicateTransitionRoute(
                "Duplicate transition from '{$transition->from}' to '{$transition->to}'"
            );
        }

        return $transition;
    }

    /**
     * @return Transition | null
     */
    public function findByName($name)
    {
        return array_values(array_filter($this->transitions, function ($transition) use ($name) {
            return $transition->name == $name;
        }))[0] ?? null;
    }

    /**
     * @return Transition | null
     */
    public function findAvailableByName($name)
    {
        return array_values(array_filter($this->transitions, function ($transition) use ($name) {
            return $transition->name == $name && $transition->isAvailable();
        }))[0] ?? null;
    }

    /**
     * @return Transition | null
     */
    public function findByRoute($from, $to)
    {
        return array_values(array_filter($this->transitions, function ($transition) use ($from, $to) {
            return $transition->from == $from && $transition->to == $to;
        }))[0] ?? null;
    }

    public function count()
    {
        return count($this->transitions);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->transitions);
    }
}

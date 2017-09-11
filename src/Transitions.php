<?php

namespace Konsulting\StateMachine;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class Transitions implements Countable, IteratorAggregate
{
    protected $transitions         = [];
    protected $pushWithDefaultCall = true;

    public function pushMany($transitions = [])
    {
        foreach ($transitions as $transition) {
            $this->push($transition);
        }

        return $this;
    }

    public function push(Transition $transition)
    {
        $this->transitions[] = $this->guardDuplicates(
            $transition->useDefaultCall($this->pushWithDefaultCall)
        );

        return $this;
    }

    protected function guardDuplicates(Transition $transition)
    {
        if ($this->findByName($transition->name)) {
            throw new Exceptions\DuplicateTransitionName(
                "Duplicate transition '{$transition->name}'"
            );
        }

        if ($this->findByRoute($transition->from, $transition->to)) {
            throw new Exceptions\DuplicateTransitionRoute(
                "Duplicate transition from '{$transition->from}' to '{$transition->to}'"
            );
        }

        return $transition;
    }

    /**
     * @param $name
     *
     * @return Transition | null
     */
    public function findByName($name)
    {
        return array_values(array_filter($this->transitions, function ($transition) use ($name) {
            return $transition->name == $name;
        }))[0] ?? null;
    }

    /**
     * @param $from
     * @param $to
     *
     * @return Transition | null
     */
    public function findByRoute($from, $to)
    {
        return array_values(array_filter($this->transitions, function ($transition) use ($from, $to) {
            return $transition->from == $from && $transition->to == $to;
        }))[0] ?? null;
    }

    public function count() {
        return count($this->transitions);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->transitions);
    }

    public function pushWithDefaultCall($value)
    {
        $this->pushWithDefaultCall = $value;

        return $this;
    }
}

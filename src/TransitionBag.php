<?php

namespace Konsulting\StateMachine;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class TransitionBag implements Countable, IteratorAggregate
{
    /** @var Transition[] */
    protected $transitions = [];

    /** @var TransitionFactory $transitionFactory */
    protected $transitionFactory;

    /**
     * Transitions constructor.
     *
     * @param TransitionFactory|null $transitionFactory
     */
    public function __construct(TransitionFactory $transitionFactory = null)
    {
        $this->setTransitionFactory($transitionFactory);
    }

    /**
     * @param TransitionFactory|null $transitionFactory
     * @return $this
     */
    public function setTransitionFactory(TransitionFactory $transitionFactory = null)
    {
        $this->transitionFactory = $transitionFactory ?: new TransitionFactory;

        return $this;
    }

    /**
     * @param StateMachine $stateMachine
     * @return $this
     */
    public function setStateMachine(StateMachine $stateMachine)
    {
        $this->transitionFactory->setStateMachine($stateMachine);

        return $this;
    }

    /**
     * @param array $transitions
     * @return $this
     * @throws Exceptions\DuplicateTransitionRoute
     */
    public function pushMany($transitions = [])
    {
        foreach ($transitions as $transition) {
            $this->push($transition);
        }

        return $this;
    }

    /**
     * Push a transition on to the end of the array. The transition may be an instance of Transition or the name as a
     * string if accompanied by the other parameters required to construct a transition..
     *
     * @see TransitionFactory::make() For the contents of ...$arguments if used.
     *
     * @param string|Transition $name
     * @param array             ...$arguments
     * @return $this
     * @throws Exceptions\DuplicateTransitionRoute
     * @throws Exceptions\StateMachineException
     */
    public function push($name, ...$arguments)
    {
        $transition = $name instanceof Transition
            ? $name
            : $this->transitionFactory->make($name, ...$arguments);

        $this->transitions[] = $this->guardDuplicates($transition);

        return $this;
    }

    /**
     * Get the last transition.
     *
     * @return Transition
     */
    public function last()
    {
        return end($this->transitions);
    }

    /**
     * Guard against duplicate transitions.
     *
     * @param Transition $transition
     * @return Transition
     * @throws Exceptions\DuplicateTransitionRoute
     */
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
     * Find a transition by name.
     *
     * @param string $name
     * @return Transition|null
     */
    public function findByName($name)
    {
        $transitions = array_filter($this->transitions, function (Transition $transition) use ($name) {
            return $transition->name === $name;
        });

        return array_shift($transitions);
    }

    /**
     * Find an available transition by name.
     *
     * @param string $name
     * @return Transition|null
     */
    public function findAvailableByName($name)
    {
        $transitions = array_filter($this->transitions, function (Transition $transition) use ($name) {
            return $transition->name == $name && $transition->isAvailable();
        });

        return array_shift($transitions);
    }

    /**
     * @param string $from
     * @param string $to
     * @return Transition|null
     */
    public function findByRoute($from, $to)
    {
        return array_values(array_filter($this->transitions, function ($transition) use ($from, $to) {
                return $transition->from == $from && $transition->to == $to;
            }))[0] ?? null;
    }

    /**
     * Get the number of transitions.
     *
     * @return int
     */
    public function count()
    {
        return count($this->transitions);
    }

    /**
     * @return ArrayIterator|\Traversable
     */
    public function getIterator()
    {
        return new ArrayIterator($this->transitions);
    }
}

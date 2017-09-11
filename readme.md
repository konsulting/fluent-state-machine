# Fluent State Machine

A simple fluent implementation of a state machine. If you've ever battled with trying to control and report state for an object in your php project - a state machine will help.

There are a few notable php state machine libraries around already - but didn't quite fit right, although we've used them in projects before. This is not an exhaustive list.

- [yohang/finite](https://github.com/yohang/Finite)
- [winzou/state-machine](https://github.com/winzou/state-machine)
- [definitely246/state-machine](https://github.com/definitely246/state-machine)
- [Symfony WorkFlow Component](https://symfony.com/doc/current/components/workflow.html) can be used as a State Machine.

## Installation

`composer require konsulting/state-machine`

## Simple Example

We can construct a basic state machine easily, using a door as an example:

```php
    use Konsulting\StateMachine\StateMachine;

    $door = new StateMachine(['closed', 'open'])
        ->addTransition('open')->from('closed')->to('open')
        ->addTransition('close')->from('open')->to('closed');
```

When constructing a StateMachine, the first state is assumed to be the default.

We can then try to transition the state machine to a new state:

```php
    $door->transition('open'); // will complete successfully

    $door->transition('close'); // will throw a TransitionFailed Exception
```

We can also check if a transition is possible:

```php
    $door->can('open'); // returns true

    $door->can('close'); // returns false
```

## Real usage

In real usage, we will have a object (model) where the state machine is responsible for controlling the transitions that can be applied (and therefore controlling the models behaviour)

This can be accomplished two ways with this library.

1. We can attach a model to the state machine, and the state machine can manipulate the model. In very simple cases, this may be enough.

2. We can attach the state machine to a model, and the model's methods use the state machine to determine if it is able to proceed with an action.

### Real usage 1 - attach a model to the state machine

As you will see, only the state machine retains the state information and we use it to control the flow in the script.

```php
    use Konsulting\StateMachine\StateMachine;

    $simpleDoor = new SimpleDoor();

    $sm = new StateMachine(['closed', 'open'])
        ->setModel($state)
        ->addTransition('open')->from('closed')->to('open')
        ->addTransition('close')->from('open')->to('closed');

    $sm->transition('open');     // outputs opening
    echo $sm->getCurrentState(); // outputs open
    $sm->transition('close');    // outputs closing
    echo $sm->getCurrentState(); // outputs closed
```

```php
    class SimpleDoor
    {
        public function open()
        {
            echo "opening";
        }

        public function close()
        {
            echo "closing";
        }
    }
```

*Side note:*  This example makes use of automatic wiring to use a model method called the same name as the transition (in camelCase). We can also define a method specifically by passing a string, or any other [callable](http://php.net/manual/en/language.types.callable.php).


### Real usage 2 - attach the state machine to a model

For this we extend the AttachableStateMachine which is set up to allow us to programmatically define the state machine, and accepts a model as its' constructor.

The end point is that we use the model in the natural manner we want to.

```php
    $door = new Door('closed');

    $door->close(); // throws TransitionFailed Exception.

    $door->open();  // outputs "I am opening"
    $door->close(); // outputs "I am closing"
```

In the door class' methods we pass through a callback to be run as part of the transition. We are also able to pass through a callback to be run if the transition fails (instead of throwing an exception).

```php

class Door
{
    public    $state;
    protected $stateMachine;

    public function __construct($state)
    {
        $this->state = $state;
        $this->stateMachine = new AttachedStateMachine($this);
    }

    public function open()
    {
        $this->stateMachine->transition('open', function () {
            echo "I am opening";
        });
    }

    public function close()
    {
        $this->stateMachine->transition('close', function () {
             echo "I am closing";
        });
    }
}
```

The AttachedStateMachine defines itself during construction. It grabs the current status from the model, and makes sure to stamp it back when setting the current status.

We also stop the auto wiring, so the state machine doesn't end up in an infinite loop trying to call it's calling method.

```php
use Konsulting\StateMachine\AttachableStateMachine;
use Konsulting\StateMachine\StateMachine;
use Konsulting\StateMachine\TransitionFactory;
use Konsulting\StateMachine\Transitions;

class AttachedStateMachine extends AttachableStateMachine
{
    protected function define()
    {
        $transitionFactory = (new TransitionFactory)->useDefaultCall(false);
        $transitions = new Transitions($transitionFactory);

        $this->setTransitions($transitions)
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
```

## Contributing

Contributions are welcome and will be fully credited. We will accept contributions by Pull Request.

Please:

* Use the PSR-2 Coding Standard
* Add tests, if you’re not sure how, please ask.
* Document changes in behaviour, including readme.md.

## Testing
We use [PHPUnit](https://phpunit.de)

Run tests using PHPUnit: `vendor/bin/phpunit`

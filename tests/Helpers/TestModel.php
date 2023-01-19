<?php

namespace Tests\Helpers;

class TestModel
{
    public $record;
    public $state;

    public function openDoor()
    {
        $this->record = 'Opening Door';

        return $this->record;
    }

    public function open()
    {
        $this->record = 'Opening';

        return $this->record;
    }
}

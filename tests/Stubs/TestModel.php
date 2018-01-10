<?php

namespace Tests\Stubs;

class TestModel
{
    public $record;

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

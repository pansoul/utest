<?php

namespace UTest\Kernel\User;

use UTest\Kernel\Form;

final class EmptyUser
{
    private $uid;

    public function __construct($id)
    {
        $this->uid = $id;
    }

    private function __clone()
    {
        //
    }

    public function __call($name, $arguments)
    {
        print Form::warning("Не удалось вызвать метод '{$name}'! Объект пользователя[{$this->uid}] не создан");
    }

    public function __callStatic($name, $arguments)
    {
        print Form::warning("Не удалось вызвать метод '{$name}'! Объект пользователя[{$this->uid}] не создан");
    }
}

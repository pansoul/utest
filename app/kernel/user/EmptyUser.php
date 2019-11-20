<?php

namespace UTest\Kernel\User;

use UTest\Kernel\Errors\AppException;

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
        throw new AppException("Не удалось вызвать метод '{$name}'! User[{$this->uid}] не создан");
    }

    public function __callStatic($name, $arguments)
    {
        throw new AppException("Не удалось вызвать метод '{$name}'! User[{$this->uid}] не создан");
    }
}

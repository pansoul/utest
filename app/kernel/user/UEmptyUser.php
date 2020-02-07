<?php

final class UEmptyUser {
    
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
        print USiteErrors::warning("Объект пользователя (Id = {$this->uid}) не создан");        
    }
    
    public function __callStatic($name, $arguments)
    {
        print USiteErrors::warning("Объект пользователя (Id = {$this->uid}) не создан");        
    }
}

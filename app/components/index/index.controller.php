<?php

class IndexController extends USiteController {
    
    protected $routeMap = array(            
        'setTitle' => 'Добро пожаловать'
    );
    
    public function run() 
    {
        $result = $this->model->doAction($this->action);        
        return $this->loadView('mainform', $result);        
    }
}

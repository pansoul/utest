<?php

class AdminProfileController extends USiteController {

    protected $routeMap = array(
        'setTitle' => 'Изменение пароля'
    );

    public function run()
    {           
        $result = $this->model->doAction($this->action);        
        $html = $this->loadView('', $result);        
        return $html;                
    }
}
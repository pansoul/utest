<?php

class AdminSettingController extends USiteController {

    protected $routeMap = array(    
        'actionDefault' => 'show',
        'setTitle' => 'Информация о вузе'
    );

    public function run()
    {           
        $result = $this->model->doAction($this->action);        
        $html = $this->loadView('', $result);        
        return $html;                
    }
}
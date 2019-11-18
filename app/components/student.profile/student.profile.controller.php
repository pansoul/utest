<?php

class StudentProfileController extends USiteController {

    protected $routeMap = array(
        'setTitle' => 'Личные данные'
    );

    public function run()
    {           
        $result = $this->model->doAction($this->action);        
        $html = $this->loadView('', $result);        
        $this->putContent($html);
    }
}
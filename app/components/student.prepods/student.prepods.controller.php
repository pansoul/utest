<?php

class StudentPrepodsController extends USiteController {

    protected $routeMap = array(
        'setTitle' => 'Преподаватели'      
    );

    public function run()
    {
        $result = $this->model->doAction($this->action);        
        $html = $this->loadView('', $result);        
        $this->putModContent($html);
    }

}

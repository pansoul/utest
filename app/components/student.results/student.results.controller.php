<?php

class StudentResultsController extends USiteController {    

    protected $routeMap = array(
        'setTitle' => 'Результаты тестирования',
        'actionDefault' => 'testlist',
        'paramsPath' => array(
            'testlist' => '/<tid>',           
        ),
        'params' => array(
            'tid' => array(
                'mask' => '',
                'rule' => '[0-9]',
                'default' => 0
            )
        )
    );

    public function run()
    {
        $result = $this->model->doAction($this->action);              
        
        switch ($this->action) {
            case 'testlist':
                $html = $this->model->vars['tid'] 
                    ? $this->loadView('result', $result)
                    : $this->loadView($this->action, $result);
                break;
            
            default:
                $html = $this->loadView($this->action, $result);
                break;
        }        
        
        $this->putModContent($html);
    }

}

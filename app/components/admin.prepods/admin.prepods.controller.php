<?php

class AdminPrepodsController extends USiteController {

    protected $routeMap = array(
        'setTitle' => 'Преподаватели',
        'actionDefault' => 'prepod',
        'paramsPath' => array(            
            'edit' => '/<id>',
            'delete' => '/<id>'
        ),
        'params' => array(            
            'id' => array(
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
            case 'edit':
                $result = $this->model->doAction($this->action, (array)$this->model->vars['id']);                
                $html = $this->loadView('newprepod', $result);
                break;
            
            case 'delete':
                $result = $this->model->doAction($this->action, array($this->model->vars['id']));
                break;
            
            default:
                $html = $this->loadView($this->action, $result);
                break;
        }        
        
        return $html;
    }

}

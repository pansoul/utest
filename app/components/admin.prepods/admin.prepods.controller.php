<?php

class AdminPrepodsController extends USiteController {

    protected $routeMap = array(
        'setTitle' => 'Преподаватели',
        'actionMain' => 'prepod',        
        'actionsPath' => array(                        
            'edit' => '/edit/<id>',
            'delete' => '/delete/<id>',
            'newPrepod' => '/newprepod'
        ),
        'varsRule' => array(
            'id' => '[0-9]',
        )
    );

    public function run()
    {   
        switch ($this->action) {
            case 'edit':
                $result = $this->model->doAction($this->action, array($this->model->vars['id']));                
                $html = $this->loadView('newprepod', $result);
                break;
            
            case 'delete':
                $result = $this->model->doAction($this->action, array($this->model->vars['id']));
                break;
            
            default:
                $result = $this->model->doAction($this->action);        
                $html = $this->loadView($this->action, $result);
                break;
        }        
        
        $this->putContent($html);
    }

}

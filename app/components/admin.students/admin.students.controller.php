<?php

namespace UTest\Components;

class AdminStudentsController extends USiteController {

    protected $routeMap = array(
        'setTitle' => 'Группы',
        'actionMain' => 'group',
        'actionsPath' => array(            
            'newGroup' => '/newgroup',
            'editGroup' => '/editgroup/<id>',     
            
            'student' => '/<group_code>',            
            'newstudent' => '/<group_code>/newstudent',            
            'editstudent' => '/<group_code>/editstudent/<id>',            
            
            'delete' => '/delete/<type>/<id>'
        ),
        'varsRule' => array(
            'group_code' => '[-_a-zA-Z0-9]',            
            'id' => '[0-9]',
            'type' => '[a-zA-Z]',
        ),        
    );

    public function run()
    {
        switch ($this->action) {
            case 'group':
            case 'student':
                $result = $this->model->doAction($this->action, $this->model->vars['group_code']);
                if ($this->model->vars['group_code']) {
                    $html = $this->loadView('student', $result);
                } else {
                    $html = $this->loadView('group', $result);
                }
                break;
            
            case 'editGroup':
                $result = $this->model->doAction($this->action, $this->model->vars['id']);
                $html = $this->loadView('newgroup', $result);
                break;
            
            case 'editStudent':
                $result = $this->model->doAction($this->action, $this->model->vars['id']);                
                $html = $this->loadView('newstudent', $result);
                break;
            
            case 'delete':
                $result = $this->model->doAction($this->action, array($this->model->vars['type'], $this->model->vars['id']));
                break;
            
            default:
                $result = $this->model->doAction($this->action);
                $html = $this->loadView($this->action, $result);
                break;
        }        
        
        $this->putContent($html);
    }

}

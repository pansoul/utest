<?php

class AdminStudentsController extends USiteController {

    protected $routeMap = array(
        'setTitle' => 'Группы',
        'actionDefault' => 'group',
        'paramsPath' => array(
            'group' => '/<group_code>',
            'newstudent' => '/<in>',
            'editgroup' => '/<id>',
            'editstudent' => '/<id>',
            'delete' => '/<type>/<id>'
        ),
        'params' => array(
            'group_code' => array(
                'mask' => '',
                'rule' => '[-_a-zA-Z0-9]',
                'default' => 0
            ),
            'in' => array(
                'mask' => '',
                'rule' => '[-_a-zA-Z0-9]',
                'default' => 0
            ),
            'id' => array(
                'mask' => '',
                'rule' => '[0-9]',
                'default' => 0
            ),
            'type' => array(
                'mask' => '',
                'rule' => '[a-zA-Z]',
                'default' => 0
            )
        )
    );

    public function run()
    {
        $result = $this->model->doAction($this->action);        
        
        switch ($this->action) {
            case 'group':
                if ($this->model->vars['group_code'])
                    $html = $this->loadView('student', $result);
                else
                    $html = $this->loadView($this->action, $result);
                break;
            
            case 'editgroup':
                $result = $this->model->doAction($this->action, (array)$this->model->vars['id']);
                $html = $this->loadView('newgroup', $result);
                break;
            
            case 'editstudent':
                $result = $this->model->doAction($this->action, (array)$this->model->vars['id']);                
                $html = $this->loadView('newstudent', $result);
                break;
            
            case 'delete':
                $result = $this->model->doAction($this->action, array($this->model->vars['type'], $this->model->vars['id']));
                break;
            
            default:
                $html = $this->loadView($this->action, $result);
                break;
        }        
        
        return $html;
    }

}

<?php

class AdminUniversityController extends USiteController {

    protected $routeMap = array(
        'setTitle' => 'Вуз',
        'actionDefault' => 'faculty',
        'paramsPath' => array(
            'faculty' => '/<faculty_code>',
            'newspeciality' => '/<in>',
            'editfaculty' => '/<id>',
            'editspeciality' => '/<id>',
            'delete' => '/<type>/<id>'
        ),
        'params' => array(
            'faculty_code' => array(
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
            case 'faculty':
                if ($this->model->vars['faculty_code'])
                    $html = $this->loadView('speciality', $result);
                else
                    $html = $this->loadView($this->action, $result);
                break;
            
            case 'editfaculty':
                $result = $this->model->doAction($this->action, (array)$this->model->vars['id']);
                $html = $this->loadView('newfaculty', $result);
                break;
            
            case 'editspeciality':
                $result = $this->model->doAction($this->action, (array)$this->model->vars['id']);                
                $html = $this->loadView('newspeciality', $result);
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
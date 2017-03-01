<?php

class AdminUniversityController extends USiteController {

    protected $routeMap = array(
        'setTitle' => 'Вуз',
        'actionMain' => 'faculty',
        'actionsPath' => array(                        
            'newFaculty' => '/newfaculty',
            'editFaculty' => '/editfaculty/<id>',
            'speciality' => '/<faculty_code>',
            'newSpeciality' => '/<faculty_code>/newspeciality',
            'delete' => '/delete/<type>/<id>',
            
                        
            
            
            
            //'newspeciality' => '/<in>',
            //'editfaculty' => '/<id>',
            //'editspeciality' => '/<id>',
            //'delete' => '/<type>/<id>'
        ),
        /*'varsRules' => array(
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
        )*/
        'varsRule' => array(
            'faculty_code' => '[-_a-zA-Z0-9]',                            
            'in' => '[-_a-zA-Z0-9]',
            'id' => '[0-9]',
            'type' => '[a-zA-Z]'
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
            
            case 'editFaculty':
                $result = $this->model->doAction($this->action, $this->model->vars['id']);
                $html = $this->loadView('newfaculty', $result);
                break;
            
            /*case 'editSpeciality':
                $result = $this->model->doAction($this->action, $this->model->vars['id']);                
                $html = $this->loadView('newspeciality', $result);
                break;
            
            case 'delete':
                $result = $this->model->doAction($this->action, array($this->model->vars['type'], $this->model->vars['id']));
                break;
            
            default:
                $html = $this->loadView($this->action, $result);
                break;*/
        }        
        
        $this->putModContent($html);
    }

}
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
            'editSpeciality' => '/<faculty_code>/editspeciality/<id>',
            'delete' => '/delete/<type>/<id>',
        ),
        'varsRule' => array(
            'faculty_code' => '[-_a-zA-Z0-9]',                            
            'in' => '[-_a-zA-Z0-9]',
            'id' => '[0-9]',
            'type' => '[a-zA-Z]'
        )
    );

    public function run()
    {
        switch ($this->action) {
            case 'faculty':
            case 'speciality':
                $result = $this->model->doAction($this->action);
                if ($this->model->vars['faculty_code'])
                    $html = $this->loadView('speciality', $result);
                else
                    $html = $this->loadView('faculty', $result);
                break;
            
            case 'editFaculty':
                $result = $this->model->doAction($this->action, $this->model->vars['id']);                
                $html = $this->loadView('newfaculty', $result);
                break;
            
            case 'editSpeciality':
                $result = $this->model->doAction($this->action, $this->model->vars['id']);                
                $html = $this->loadView('newspeciality', $result);
                break;
            
            case 'delete':
                $result = $this->model->doAction($this->action, array($this->model->vars['type'], $this->model->vars['id']));
                break;
            
            default:
                $result = $this->model->doAction($this->action);
                $html = $this->loadView($this->action, $result);
                break;
        }        
        
        $this->putModContent($html);
    }

}
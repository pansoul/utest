<?php

class StudentMaterialsController extends USiteController {    

    protected $routeMap = array(
        'setTitle' => 'Материал по дисциплинам',
        'actionDefault' => 'my',
        'paramsPath' => array(
            'my' => '/<subject_code>',           
        ),
        'params' => array(
            'subject_code' => array(
                'mask' => '',
                'rule' => '[-_a-zA-Z0-9]',
                'default' => 0
            )
        )
    );

    public function run()
    {
        $result = $this->model->doAction($this->action);              
        
        switch ($this->action) {
            case 'my':
                $html = $this->model->vars['subject_code'] 
                    ? $this->loadView('mymaterial', $result)
                    : $this->loadView($this->action, $result);
                break;
            
            default:
                $html = $this->loadView($this->action, $result);
                break;
        }        
        
        $this->putContent($html);
    }

}

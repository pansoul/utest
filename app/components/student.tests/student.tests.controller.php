<?php

class StudentTestsController extends USiteController {
    
    public $arTabs;

    protected $routeMap = array(
        'setTitle' => 'Тесты по дисциплинам',
        'actionDefault' => 'my',
        'paramsPath' => array(
            'my' => '/<subject_code>/<tid>',
            'run' => '/<id>',
            'q' => '/<num>'            
        ),
        'params' => array(
            'subject_code' => array(
                'mask' => '',
                'rule' => '[-_a-zA-Z0-9]',
                'default' => 0
            ),
            'tid' => array(
                'mask' => 'test-<?>',
                'rule' => '[0-9]',
                'default' => 0
            ),
            'id' => array(
                'mask' => '',
                'rule' => '[0-9]',
                'default' => 0
            ),
            'num' => array(
                'mask' => '',
                'rule' => '[0-9]',
                'default' => 0
            )
        )
    );
    
    // Список контроллеров, для которых не нужно обрабатывать "общий" result
    private $arNotResult = array(
        //
    );
    
    public function run()
    {  
        if (!in_array($this->action, $this->arNotResult))
            $result = $this->model->doAction($this->action);              
        
        switch ($this->action) {
            case 'my':
                if ($this->model->vars['tid'])
                    $html = $this->loadView('test', $result);
                elseif ($this->model->vars['subject_code'])
                    $html = $this->loadView('mytests', $result);
                else
                    $html = $this->loadView($this->action, $result);
                break;
                
            // for ajax
            case 'run':
            case 'q':
            case 'end':
                echo $result;
                exit;
                break;
            
            default:
                $html = $this->loadView($this->action, $result);
                break;
        }        
        
        return $html;
    }

}

<?php

class PrepodResultsController extends USiteController {

    protected $routeMap = array(
        'setTitle' => 'Результаты тестирования',
        'actionDefault' => 'for',
        'paramsPath' => array(
            'for' => '/<group_code>/<subject_code>/<tid>',
            'sretake' => '/<for_tid>/<uid>',
            'gretake' => '/<for_tid>/<gid>',
            'r' => '/<for_tid>/<uid>'
        ),
        'params' => array(
            'subject_code' => array(
                'mask' => '',
                'rule' => '[-_a-zA-Z0-9]',
                'default' => 0
            ),
            'group_code' => array(
                'mask' => '',
                'rule' => '[-_a-zA-Z0-9]',
                'default' => 0
            ),            
            'tid' => array(
                'mask' => '',
                'rule' => '[0-9]',
                'default' => 0
            ),
            'for_tid' => array(
                'mask' => 'for-<?>',
                'rule' => '[0-9]',
                'default' => 0
            ),
            'uid' => array(
                'mask' => '',
                'rule' => '[0-9]',
                'default' => 0
            ),
            'gid' => array(
                'mask' => '',
                'rule' => '[0-9]',
                'default' => 0
            )
        )
    );
    
    // Список контроллеров, для которых не нужно обрабатывать "общий" result
    private $arNotResult = array(
        'sretake',
        'gretake',
    );

    public function run()
    {
        if (!in_array($this->action, $this->arNotResult))
            $result = $this->model->doAction($this->action);                      
        
        switch ($this->action) {
            case 'for':                
                if ($this->model->vars['tid'])
                    $html = $this->loadView('testlist', $result);
                elseif ($this->model->vars['subject_code'])
                    $html = $this->loadView('fortests', $result);
                elseif ($this->model->vars['group_code'])
                    $html = $this->loadView('forsubject', $result);
                else
                    $html = $this->loadView($this->action, $result);
                break;
            
            case 'sretake':
                $result = $this->model->doAction($this->action, array($this->model->vars['for_tid'], $this->model->vars['uid']));
                $html = $this->loadView('sretake', $result);
                break;
            
            case 'gretake':
                $result = $this->model->doAction($this->action, array($this->model->vars['for_tid'], $this->model->vars['gid']));
                $html = $this->loadView('gretake', $result);
                break;
            
            case 'r':                
                $result = $this->model->doAction($this->action, array($this->model->vars['for_tid'], $this->model->vars['uid']));
                $html = $this->loadView('result', $result);
                break;
            
            default:
                $html = $this->loadView($this->action, $result);
                break;
        }        
        
        $this->putModContent($html);
    }

}

<?php

class PrepodMaterialsController extends USiteController {
    
    protected $arTabs;

    protected $routeMap = array(
        'setTitle' => 'Материал',
        'actionDefault' => 'my',
        'paramsPath' => array(
            'my' => '/<subject_code>',
            'for' => '/<group_code>/<subject_code>',
            'newmy' => '/<in>',
            'newfor' => '/<group_code>/<subject_code>',
            'newcomment' => '/<group_code>/<subject_code>',
            'editmy' => '/<id>',
            'editcomment' => '/<id>',
            'delete' => '/<type>/<id>'
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
        $this->arTabs = array(
            1 => array(
                'name' => 'Мой материал',
                'href' => USite::getModurl() . '/my'
            ),
            2 => array(
                'name' => 'Материал для групп',
                'href' => USite::getModurl() . '/for'
            )
        );
        
        $result = $this->model->doAction($this->action);              
        
        switch ($this->action) {
            case 'my':
                $html = $this->model->vars['subject_code'] 
                    ? $this->loadView('mymaterial', $result)
                    : $this->loadView($this->action, $result);
                break;
            
            case 'for':
                UAppBuilder::editBreadcrumpItem(array('name' => 'Материал для групп', 'url' => USite::getModurl().'/for'));
                if ($this->model->vars['subject_code'])
                    $html = $this->loadView('formaterial', $result);
                elseif ($this->model->vars['group_code'])
                    $html = $this->loadView('forsubject', $result);
                else
                    $html = $this->loadView($this->action, $result);
                break;
                
            case 'newfor':
            case 'newcomment':
                UAppBuilder::editBreadcrumpItem(array('name' => 'Материал для групп', 'url' => USite::getModurl().'/for'));
                $html = $this->loadView($this->action, $result);
                break;
            
            case 'editmy':
                $result = $this->model->doAction($this->action, (array)$this->model->vars['id']);
                $html = $this->loadView('newmy', $result);
                break;
            
            case 'editcomment':
                UAppBuilder::editBreadcrumpItem(array('name' => 'Материал для групп', 'url' => USite::getModurl().'/for'));
                $result = $this->model->doAction($this->action, (array)$this->model->vars['id']);                
                $html = $this->loadView('newcomment', $result);
                break;
            
            case 'delete':
                $result = $this->model->doAction($this->action, array($this->model->vars['type'], $this->model->vars['id']));
                break;
            
            default:
                $html = $this->loadView($this->action, $result);
                break;
        }        
        
        $this->putContent($html);
    }

}

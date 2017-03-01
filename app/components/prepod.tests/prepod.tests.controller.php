<?php

class PrepodTestsController extends USiteController {
    
    public $arTabs;
    
    /*
     * 'mySubjects' => '/my',
            'myTests' => '/my/<subject_code>',
            'myTestNew' => '/my/<subject_code>/new',
            'myTestEdit' => '/my/<subject_code>/<my_test_edit>',
            'myTestDelete' => '/my/<subject_code>/<my_test_delete>',
            'myTestQuestions' => '/my/<subject_code>/<tid>',
            'myTestQuestionNew' => '/my/<subject_code>/<tid>/new',
            'myTestQuestionEdit' => '/my/<subject_code>/<tid>/<my_question_edit>',
            'myTestQuestionDelete' => '/my/<subject_code>/<tid>/<my_question_delete>',
     * 
     * 
     * 'mySubjects' => '/my',
'myTests' => '/my/<subject_code>',
'myTestNew' => '/my/<subject_code>/new',
'myTestEdit' => '/my/<subject_code>/<my_test_edit>',
'myTestDelete' => '/my/<subject_code>/<my_test_delete>',
'myTestQuestions' => '/my/<subject_code>/<tid>',
'myTestQuestionNew' => '/my/<subject_code>/<tid>/new',
'myTestQuestionEdit' => '/my/<subject_code>/<tid>/<my_question_edit>',
'myTestQuestionDelete' => '/my/<subject_code>/<tid>/<my_question_delete>',
     */

    protected $routeMap = array(
        'setTitle' => 'Тесты',
        'actionDefault' => 'my',
        'paramsPath' => array(
            'my' => '/<subject_code>/<tid>',
            'for' => '/<group_code>/<subject_code>',            
            'newmytest' => '/<in>',
            'newmyquestion' => '/<in_tid>',
            'newtype' => '/<qtype>',            
            'newfor' => '/<group_code>/<subject_code>',
            'editmytest' => '/<id>',
            'editfortest' => '/<id>',
            'editmyquestion' => '/<in_tid>/<id>',            
            'delete' => '/<type>/<id>',
            'delquestion' => '/<tid>/<qid>',
            'delanswer' => '/<tid>/<qid>/<id>'
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
            'qtype' => array(
                'mask' => '',
                'rule' => '[-_a-zA-Z]',
                'default' => 'one'
            ),
            'id' => array(
                'mask' => '',
                'rule' => '[0-9]',
                'default' => 0
            ),
            'qid' => array(
                'mask' => '',
                'rule' => '[0-9]',
                'default' => 0
            ),
            'tid' => array(
                'mask' => 'test-<?>',
                'rule' => '[0-9]',
                'default' => 0
            ),
            'in_tid' => array(
                'mask' => 'in-<?>',
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
    
    // Список контроллеров, для которых не нужно обрабатывать "общий" result
    private $arNotResult = array(
        'editmytest',
        'editfortest',
        'editmyquestion',
        'delquestion',
        'delanswer',
        'delete',
        'answerdisplay'
    );

    public function run()
    {
        $this->arTabs = array(
            1 => array(
                'name' => 'Мои тесты',
                'href' => USite::getModurl() . '/my'
            ),
            2 => array(
                'name' => 'Назначенные тесты',
                'href' => USite::getModurl() . '/for'
            )
        );
        
        if (!in_array($this->action, $this->arNotResult)) {
            $result = $this->model->doAction($this->action);              
        }
        
        switch ($this->action) {
            case 'my':
                if ($this->model->vars['tid'])
                    $html = $this->loadView('myquestions', $result);
                elseif ($this->model->vars['subject_code'])
                    $html = $this->loadView('mytests', $result);
                else
                    $html = $this->loadView($this->action, $result);
                break;
            
            case 'for':
                UAppBuilder::editBreadcrumpItem(array('name' => 'Назначенные тесты', 'url' => USite::getModurl().'/for'));
                if ($this->model->vars['subject_code'])
                    $html = $this->loadView('fortests', $result);
                elseif ($this->model->vars['group_code'])
                    $html = $this->loadView('forsubject', $result);
                else
                    $html = $this->loadView($this->action, $result);
                break;
                
            case 'newfor':
                UAppBuilder::editBreadcrumpItem(array('name' => 'Назначенные тесты', 'url' => USite::getModurl().'/for'));
                $html = $this->loadView($this->action, $result);
                break;
            
            case 'editmytest':
                $result = $this->model->doAction($this->action, (array)$this->model->vars['id']);
                $html = $this->loadView('newmytest', $result);
                break;
            
            case 'editmyquestion':
                $result = $this->model->doAction($this->action, array($this->model->vars['in_tid'], $this->model->vars['id']));                
                $html = $this->loadView('newmyquestion', $result);
                break;
            
            case 'editfortest':
                UAppBuilder::editBreadcrumpItem(array('name' => 'Назначенные тесты', 'url' => USite::getModurl().'/for'));
                $result = $this->model->doAction($this->action, (array)$this->model->vars['id']);                
                $html = $this->loadView('newfor', $result);
                break;
            
            // for ajax
            case 'newtype':
                $html = $this->loadView('answer_' . $this->model->vars['qtype'], $result);
                echo $html;
                exit;
                break;
            
            // for ajax
            case 'delanswer':                
                $result = $this->model->doAction($this->action, array($this->model->vars['tid'], $this->model->vars['qid'], $this->model->vars['id']));
                echo $result;
                exit;
                break;
            
            case 'delquestion':
                $result = $this->model->doAction($this->action, array($this->model->vars['tid'], $this->model->vars['qid']));
                break;
            
            case 'delete':
                $result = $this->model->doAction($this->action, array($this->model->vars['type'], $this->model->vars['id']));
                break;
            
            case 'answerdisplay':
                $result = $this->model->doAction($this->action, $this->actionArgs);     
                $html = $this->loadView('answer_'.$this->actionArgs[0], $result);
                break;
            
            default:
                $html = $this->loadView($this->action, $result);
                break;
        }        
        
        $this->putModContent($html);
    }

}

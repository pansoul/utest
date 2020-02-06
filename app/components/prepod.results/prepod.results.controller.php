<?php

namespace UTest\Components;

class PrepodResultsController extends \UTest\Kernel\Component\Controller
{
    protected $routeMap = array(
        'title' => 'Результаты тестирования',
        'add_breadcrumb' => true,
        'action_main' => 'groups',
        'actions_params' => array(
            '/<group_code>' => [
                'action' => 'subjects',
                'title' => 'Результаты тестирования',
                'add_breadcrumb' => true
            ],
            '/<group_code>/<subject_code>' => [
                'action' => 'tests',
                'title' => 'Результаты тестирования',
                'add_breadcrumb' => true
            ],
            '/<group_code>/<subject_code>/gretake/<gid>' => [
                'action' => 'gretake',
                'title' => 'Пересдача теста',
                'add_breadcrumb' => true
            ],
            '/<group_code>/<subject_code>/<tid>' => [
                'action' => 'students',
                'title' => 'Результаты тестирования',
                'add_breadcrumb' => true
            ],
            '/<group_code>/<subject_code>/<tid>/sretake/<uid>' => [
                'action' => 'sretake',
                'title' => 'Пересдача теста',
                'add_breadcrumb' => true
            ],
        ),
        'vars_rules' => array(
            'subject_code' => '[-_a-zA-Z0-9]',
            'group_code' => '[-_a-zA-Z0-9]',
            'tid' => '[0-9]',
            'for_tid' => '[0-9]',
            'uid' => '[0-9]',
            'gid' => '[0-9]',
        )
    );
    
    // Список контроллеров, для которых не нужно обрабатывать "общий" result
    private $arNotResult = array(
        'sretake',
        'gretake',
    );

    public function run()
    {
        /*if (!in_array($this->action, $this->arNotResult)) {
            $this->model->doAction($this->action);
        }*/
        
        switch ($this->action) {
            case 'subjects':
                $this->doAction($this->action, $this->getVars('group_code'));
                $html = $this->loadView($this->action);
                break;

            case 'tests':
                $this->doAction($this->action, $this->getVars(['group_code', 'subject_code']));
                $html = $this->loadView($this->action);
                break;


            case 'for':                
                if ($this->model->vars['tid'])
                    $html = $this->loadView('testlist');
                elseif ($this->model->vars['subject_code'])
                    $html = $this->loadView('fortests');
                elseif ($this->model->vars['group_code'])
                    $html = $this->loadView('forsubject');
                else
                    $html = $this->loadView($this->action);
                break;
            
            case 'sretake':
                $this->doAction($this->action, $this->getVars(['for_tid', 'uid']));
                $html = $this->loadView($this->action);
                break;
            
            case 'gretake':
                $this->doAction($this->action, $this->getVars(['for_tid', 'gid']));
                $html = $this->loadView($this->action);
                break;
            
            case 'r':                
                $this->model->doAction($this->action, array($this->model->vars['for_tid'], $this->model->vars['uid']));
                $html = $this->loadView('result');
                break;
            
            default:
                $this->doAction($this->action);
                $html = $this->loadView($this->action);
                break;
        }        
        
        $this->putContent($html);
    }
}
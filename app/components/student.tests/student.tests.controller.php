<?php

namespace UTest\Components;

class StudentTestsController extends \UTest\Kernel\Component\Controller
{
    protected $routeMap = array(
        'title' => 'Тесты по дисциплинам',
        'add_breadcrumb' => true,
        'action_main' => 'subjects',
        'actions_params' => array(
            '/<subject_code>' => [
                'action' => 'test_list',
                'title' => 'Список тестов',
                'add_breadcrumb' => true
            ],
            '/<subject_code>/test-<id>' => [
                'action' => 'run',
                'title' => 'Прохождение теста',
                'add_breadcrumb' => true
            ],

            '/ajax/test-<id>/start' => [
                'action' => 'ajax_start'
            ],
            '/ajax/test-<id>/goto/<number>' => [
                'action' => 'ajax_goto'
            ],
            '/ajax/test-<id>/finish' => [
                'action' => 'ajax_finish'
            ],
        ),
        'vars_rules' => array(
            'subject_code' => '[-_a-zA-Z0-9]',
            'id' => '[0-9]',
            'number' => '[a-z0-9]',
        )
    );

    public function run()
    {
        switch ($this->action) {
            case 'test_list':
                $this->doAction($this->action, $this->getVars('subject_code'));
                $html = $this->loadView($this->action);
                break;

            case 'run':
                $this->doAction($this->action, $this->getVars(['subject_code', 'id']));
                $html = $this->loadView($this->action);
                break;

            case 'ajax_start':
                $this->doAction($this->action, $this->getVars('id'));
                $this->outputForAjax($this->getActionData(), self::AJAX_MODE_JSON);
                break;

            case 'ajax_goto':
                $this->doAction($this->action, $this->getVars(['id', 'number']));
                $this->outputForAjax($this->getActionData(), self::AJAX_MODE_JSON);
                break;

            case 'ajax_finish':
                $this->doAction($this->action, $this->getVars('id'));
                $this->outputForAjax($this->getActionData(), self::AJAX_MODE_JSON);
                break;

            /*case 'my':
                if ($this->model->vars['tid']) {
                    $html = $this->loadView('test', $result);
                } elseif ($this->model->vars['subject_code']) {
                    $html = $this->loadView('mytests', $result);
                } else {
                    $html = $this->loadView($this->action, $result);
                }
                break;

            // for ajax
            case 'run':
            case 'q':
            case 'end':
                echo $result;
                exit;
                break;*/

            default:
                $this->doAction($this->action);
                $html = $this->loadView($this->action);
                break;
        }

        $this->putContent($html);
    }
}

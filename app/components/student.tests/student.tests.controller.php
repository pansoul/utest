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
            'my' => '/<subject_code>/<tid>',
            'run' => '/<id>',
            'q' => '/<num>'
        ),
        'vars_rules' => array(
            'subject_code' => '[-_a-zA-Z0-9]',
            'id' => '[0-9]',
            'num' => '[0-9]',
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

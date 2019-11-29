<?php

namespace UTest\Components;

class PrepodStudentsController extends \UTest\Kernel\Component\Controller
{

    protected $routeMap = array(
        'title' => 'Группы',
        'add_breadcrumb' => true,
        'action_main' => 'group',
        'actions_params' => array(
            '/<group_code>' => [
                'action' => 'student',
                'title' => 'Cтуденты',
                'add_breadcrumb' => true
            ],
            '/<group_code>/editstudent/<id>' => [
                'action' => 'editStudent',
                'title' => 'Редактирование студента',
                'add_breadcrumb' => true,
            ]
        ),
        'vars_rules' => array(
            'group_code' => '[-_a-zA-Z0-9]',
            'in' => '[-_a-zA-Z0-9]',
            'id' => '[0-9]'
        )
    );

    public function run()
    {
        $html = '';

        switch ($this->action) {
            case 'group':
                $this->doAction($this->action);
                $html = $this->loadView($this->action);
                break;

            case 'student':
                $this->doAction($this->action, $this->getVars('group_code'));
                $html = $this->loadView($this->action);
                break;

            case 'editStudent':
                $this->doAction($this->action, $this->getVars('id'));
                $html = $this->loadView('newstudent');
                break;

            default:
                $this->doAction($this->action);
                $html = $this->loadView($this->action);
                break;
        }

        $this->putContent($html);
    }

}

<?php

namespace UTest\Components;

use UTest\Kernel\DB;

class StudentResultsController extends \UTest\Kernel\Component\Controller
{
    protected function routeMap()
    {
        return [
            'title' => 'Список завершённых тестов',
            'add_breadcrumb' => true,
            'action_main' => 'test_list',
            'actions_params' => array(
                '/<id>' => [
                    'action' => 'result',
                    'title' => 'Результаты теста',
                    'subtitle' => function ($vars) {
                        return DB::table(TABLE_STUDENT_TEST)
                            ->select('title')
                            ->find($vars['id'])['title'];
                    },
                    'add_breadcrumb' => true
                ],
            ),
            'vars_rules' => array(
                'id' => '[0-9]'
            )
        ];
    }

    public function run()
    {
        switch ($this->action) {
            case 'result':
                $this->doAction($this->action, $this->getVars('id'));
                $html = $this->loadView($this->action);
                break;

            default:
                $this->doAction($this->action);
                $html = $this->loadView($this->action);
                break;
        }

        $this->putContent($html);
    }
}
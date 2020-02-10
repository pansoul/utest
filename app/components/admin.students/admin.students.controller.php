<?php

namespace UTest\Components;

use UTest\Kernel\DB;

class AdminStudentsController extends \UTest\Kernel\Component\Controller
{
    protected function routeMap()
    {
        return [
            'title' => 'Группы',
            'subtitle' => function($vars){
                return DB::table(TABLE_UNIVER_GROUP)
                    ->select('title')
                    ->where('alias', $vars['group_code'])
                    ->first()['title'];
            },
            'add_breadcrumb' => true,
            'action_main' => 'group',
            'actions_params' => [
                '/newgroup' => [
                    'action' => 'newGroup',
                    'title' => 'Создание новой группы',
                    'add_breadcrumb' => true,
                ],
                '/editgroup/<id>' => [
                    'action' => 'editGroup',
                    'title' => 'Редактирование группы',
                    'add_breadcrumb' => true,
                ],

                '/<group_code>' => [
                    'action' => 'student',
                    'title' => 'Cтуденты',
                    'add_breadcrumb' => true,
                ],
                '/<group_code>/newstudent' => [
                    'action' => 'newStudent',
                    'title' => 'Создание нового студента',
                    'add_breadcrumb' => true,
                ],
                '/<group_code>/editstudent/<id>' => [
                    'action' => 'editStudent',
                    'title' => 'Редактирование студента',
                    'add_breadcrumb' => true,
                ],


                '/delete/<type>/<id>' => [
                    'action' => 'delete'
                ]
            ],
            'vars_rules' => [
                'group_code' => '[-_a-zA-Z0-9]',
                'id' => '[0-9]',
                'type' => '[a-zA-Z]',
            ],
        ];
    }

    public function run()
    {
        $html = '';

        switch ($this->action) {
            case 'group':
            case 'student':
                $this->doAction($this->action, $this->getVars('group_code'));
                $html = $this->loadView($this->getVars('group_code') ? 'student' : 'group');
                break;

            case 'editGroup':
                $this->doAction($this->action, $this->getVars('id'));
                $html = $this->loadView('newGroup');
                break;

            case 'editStudent':
                $this->doAction($this->action, $this->getVars('id'));
                $html = $this->loadView('newStudent');
                break;

            case 'delete':
                $this->doAction($this->action, $this->getVars(['type', 'id']));
                break;

            default:
                $this->doAction($this->action);
                $html = $this->loadView($this->action);
                break;
        }

        $this->putContent($html);
    }
}

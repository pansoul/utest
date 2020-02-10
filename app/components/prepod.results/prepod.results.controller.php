<?php

namespace UTest\Components;

use UTest\Kernel\DB;
use UTest\Kernel\User\User;

class PrepodResultsController extends \UTest\Kernel\Component\Controller
{
    protected function routeMap()
    {
        return [
            'title' => 'Результаты тестирования',
            'subtitle' => function($vars){
                return DB::table(TABLE_UNIVER_GROUP)
                    ->select('title')
                    ->where('alias', $vars['group_code'])
                    ->first()['title'];
            },
            'add_breadcrumb' => true,
            'action_main' => 'groups',
            'actions_params' => array(
                '/<group_code>' => [
                    'action' => 'subjects',
                    'title' => 'Дисциплины',
                    'subtitle' => function($vars){
                        return DB::table(TABLE_PREPOD_SUBJECT)
                            ->select('title')
                            ->where('alias', $vars['subject_code'])
                            ->first()['title'];
                    },
                    'add_breadcrumb' => true
                ],
                '/<group_code>/<subject_code>' => [
                    'action' => 'tests',
                    'title' => 'Назначенные тесты',
                    'subtitle' => function($vars){
                        return DB::table(TABLE_STUDENT_TEST)
                            ->select('title')
                            ->find($vars['atid'])['title'];
                    },
                    'add_breadcrumb' => true
                ],
                '/<group_code>/<subject_code>/gretake/<atid>' => [
                    'action' => 'retake_group',
                    'title' => 'Пересдача теста группе',
                    'add_breadcrumb' => true
                ],
                '/<group_code>/<subject_code>/<atid>' => [
                    'action' => 'students',
                    'title' => 'Студенты',
                    'add_breadcrumb' => true
                ],
                '/<group_code>/<subject_code>/<atid>/result/<uid>' => [
                    'action' => 'result',
                    'title' => 'Результат тестирования',
                    'subtitle' => function($vars){
                        return User::user($vars['uid'])->getFullName();
                    },
                    'add_breadcrumb' => true
                ],
                '/<group_code>/<subject_code>/<atid>/sretake/<uid>' => [
                    'action' => 'retake_student',
                    'title' => 'Пересдача теста студенту',
                    'subtitle' => function($vars){
                        return User::user($vars['uid'])->getFullName();
                    },
                    'add_breadcrumb' => true
                ],
            ),
            'vars_rules' => array(
                'subject_code' => '[-_a-zA-Z0-9]',
                'group_code' => '[-_a-zA-Z0-9]',
                'atid' => '[0-9]',
                'uid' => '[0-9]'
            )
        ];
    }

    public function run()
    {
        switch ($this->action) {
            case 'subjects':
                $this->doAction($this->action, $this->getVars('group_code'));
                $html = $this->loadView($this->action);
                break;

            case 'tests':
                $this->doAction($this->action, $this->getVars(['group_code', 'subject_code']));
                $html = $this->loadView($this->action);
                break;

            case 'students':
                $this->doAction($this->action, $this->getVars(['group_code', 'subject_code', 'atid']));
                $html = $this->loadView($this->action);
                break;

            case 'result':
            case 'retake_student':
                $this->doAction($this->action, $this->getVars(['atid', 'uid']));
                $html = $this->loadView($this->action);
                break;
            
            case 'retake_group':
                $this->doAction($this->action, $this->getVars(['atid', 'group_code']));
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
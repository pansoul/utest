<?php

namespace UTest\Components;

use UTest\Kernel\DB;
use UTest\Kernel\Site;

class PrepodTestsController extends \UTest\Kernel\Component\Controller
{
    protected function routeMap()
    {
        return [
            'redirect' => Site::getModUrl() . '/my',
            'actions_params' => array(
                '/my' => [
                    'action' => 'my',
                    'title' => 'Мои тесты',
                    'subtitle' => function($vars){
                        return DB::table(TABLE_PREPOD_SUBJECT)
                            ->select('title')
                            ->where('alias', $vars['subject_code'])
                            ->first()['title'];
                    },
                    'add_breadcrumb' => true,
                ],
                '/my/<subject_code>' => [
                    'action' => 'my_tests',
                    'title' => 'Список тестов',
                    'subtitle' => function($vars){
                        return DB::table(TABLE_TEST)
                            ->select('title')
                            ->find($vars['tid'])['title'];
                    },
                    'add_breadcrumb' => true
                ],
                '/my/<subject_code>/delete/<id>' => [
                    'action' => 'delete'
                ],
                '/my/<subject_code>/new' => [
                    'action' => 'my_new_test',
                    'title' => 'Создание нового теста',
                    'add_breadcrumb' => true
                ],
                '/my/<subject_code>/edit/<id>' => [
                    'action' => 'my_edit_test',
                    'title' => 'Редактирование теста',
                    'add_breadcrumb' => true
                ],
                '/my/<subject_code>/test-<tid>' => [
                    'action' => 'my_test_questions',
                    'title' => 'Список вопросов',
                    'add_breadcrumb' => true
                ],
                '/my/<subject_code>/test-<tid>/new' => [
                    'action' => 'my_new_question',
                    'title' => 'Создание нового вопроса',
                    'add_breadcrumb' => true
                ],
                '/my/<subject_code>/test-<tid>/edit/<id>' => [
                    'action' => 'my_edit_question',
                    'title' => 'Редактирование вопроса',
                    'add_breadcrumb' => true
                ],
                '/my/<subject_code>/test-<tid>/delete/<id>' => [
                    'action' => 'delete'
                ],

                '/for' => [
                    'action' => 'for',
                    'title' => 'Назначенные тесты',
                    'subtitle' => function($vars){
                        return DB::table(TABLE_UNIVER_GROUP)
                            ->select('title')
                            ->where('alias', $vars['group_code'])
                            ->first()['title'];
                    },
                    'add_breadcrumb' => true
                ],
                '/for/<group_code>' => [
                    'action' => 'for_subject',
                    'title' => 'Дисциплины',
                    'subtitle' => function($vars){
                        return DB::table(TABLE_PREPOD_SUBJECT)
                            ->select('title')
                            ->where('alias', $vars['subject_code'])
                            ->first()['title'];
                    },
                    'add_breadcrumb' => true
                ],
                '/for/<group_code>/<subject_code>' => [
                    'action' => 'for_tests',
                    'title' => 'Список назначенных тестов',
                    'add_breadcrumb' => true
                ],
                '/for/<group_code>/<subject_code>/new' => [
                    'action' => 'for_new_test',
                    'title' => 'Назначение нового теста',
                    'add_breadcrumb' => true
                ],
                '/for/<group_code>/<subject_code>/edit/<id>' => [
                    'action' => 'for_edit_test',
                    'title' => 'Редактирование назначенного теста',
                    'add_breadcrumb' => true
                ],
                '/for/<group_code>/<subject_code>/delete/<id>' => [
                    'action' => 'delete'
                ],

                '/ajax/newtype/<qtype>' => [
                    'action' => 'ajax_new_type'
                ],

                '/delete/<type>/<id>' => [
                    'action' => 'delete',
                ],
            ),
            'vars_rules' => [
                'subject_code' => '[-_a-zA-Z0-9]',
                'group_code' => '[-_a-zA-Z0-9]',
                'in' => '[-_a-zA-Z0-9]',
                'qtype' => '[-_a-zA-Z]',
                'id' => '[0-9]',
                'qid' => '[0-9]',
                'tid' => '[0-9]',
                'type' => '[a-zA-Z]'
            ]
        ];
    }

    public function run()
    {
        // Данные для построения табов
        $arTabs = array(
            'my' => array(
                'name' => 'Мои тесты',
                'href' => Site::getModurl() . '/my'
            ),
            'for' => array(
                'name' => 'Назначенные тесты',
                'href' => Site::getModurl() . '/for'
            )
        );
        $exploded = explode('/', Site::getModParamsRow(), 3);
        $tabSelected = $exploded[1] ? $exploded[1] : 'my';

        // Управление выводом табов
        $hideTabs = false;

        switch ($this->action) {
            case 'my_tests':
                $this->doAction($this->action, $this->getVars('subject_code'));
                $html = $this->loadView($this->action);
                break;

            case 'my_edit_test':
                $this->doAction($this->action, $this->getVars('id'));
                $html = $this->loadView('my_new_test');
                break;

            case 'my_test_questions':
                $this->doAction($this->action, $this->getVars(['subject_code', 'tid']));
                $html = $this->loadView($this->action);
                break;

            case 'my_edit_question':
                $this->doAction($this->action, $this->getVars(['tid', 'id']));
                $html = $this->loadView('my_new_question');
                break;

            case 'ajax_new_type':
                $html = $this->loadView('answer_' . $this->getVars('qtype'));
                $this->outputForAjax($html, self::AJAX_MODE_HTML);
                break;

            case 'for_subject':
                $this->doAction($this->action, $this->getVars('group_code'));
                $html = $this->loadView($this->action);
                break;

            case 'for_tests':
                $this->doAction($this->action, $this->getVars(['group_code', 'subject_code']));
                $html = $this->loadView($this->action);
                break;

            case 'for_edit_test':
                $this->doAction($this->action, $this->getVars('id'));
                $html = $this->loadView('for_new_test');
                break;

            case 'answer_display':
                $hideTabs = true;
                $this->doAction($this->action, [$this->actionArgs['question'], $this->actionArgs['answer'], $this->actionArgs['right']]);
                $html = $this->loadView('answer_' . $this->actionArgs['type']);
                break;

            case 'delete':
                $this->doAction($this->action, [$tabSelected, $this->getVars('id')]);
                break;

            default:
                $this->doAction($this->action);
                $html = $this->loadView($this->action);
                break;
        }

        if (!$hideTabs) {
            $tabs = $this->loadView('tabs', [
                'tabs' => $arTabs,
                'selected' => $tabSelected
            ]);
            $this->putContent($tabs);
        }

        $this->putContent($html);
    }
}

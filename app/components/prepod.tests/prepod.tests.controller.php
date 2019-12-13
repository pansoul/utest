<?php

namespace UTest\Components;

use UTest\Kernel\Site;

class PrepodTestsController extends \UTest\Kernel\Component\Controller
{
    protected $routeMap = array(
        'title' => 'Тесты',
        'add_breadcrumb' => true,
        'action_main' => 'my',
        'actions_params' => array(
            '/my' => [
                'action' => 'my'
            ],
            '/my/<subject_code>' => [
                'action' => 'my_tests',
                'title' => 'Список тестов',
                'add_breadcrumb' => true
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

            '/ajax/newtype/<qtype>' => [
                'action' => 'ajax_new_type'
            ],
            '/ajax/delanswer/test-<tid>/question-<qid>/<id>' => [
                'action' => 'ajax_delete_answer'
            ],




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
    );

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
                $this->doAction($this->action, $this->getVars('tid'));
                $html = $this->loadView('my_new_test');
                break;

            case 'my_test_questions':
                $this->doAction($this->action, $this->getVars(['subject_code', 'tid']));
                $html = $this->loadView($this->action);
                break;

            case 'answer_display':
                $hideTabs = true;
                $this->doAction($this->action, $this->actionArgs);
                $html = $this->loadView('answer_' . $this->actionArgs[0]);
                break;

            case 'my_edit_question':
                $this->doAction($this->action, $this->getVars(['tid', 'id']));
                $html = $this->loadView('my_new_question');
                break;

            case 'ajax_new_type':
                $html = $this->loadView('answer_' . $this->getVars('qtype'));
                $this->outputForAjax($html, self::AJAX_MODE_HTML);
                break;

            case 'ajax_delete_answer':
                $this->doAction($this->action, $this->getVars(['tid', 'qid', 'id']));
                $this->outputForAjax($this->getActionData(), self::AJAX_MODE_JSON);
                break;

            /*case 'my':
                if ($this->vars['tid']) {
                    $html = $this->loadView('myquestions');
                } elseif ($this->vars['subject_code']) {
                    $html = $this->loadView('mytests');
                } else {
                    $html = $this->loadView($this->action);
                }
                break;

            case 'for':
                UAppBuilder::editBreadcrumpItem(array(
                    'name' => 'Назначенные тесты',
                    'url' => USite::getModurl() . '/for'
                ));
                if ($this->vars['subject_code']) {
                    $html = $this->loadView('fortests');
                } elseif ($this->vars['group_code']) {
                    $html = $this->loadView('forsubject');
                } else {
                    $html = $this->loadView($this->action);
                }
                break;

            case 'newfor':
                UAppBuilder::editBreadcrumpItem(array(
                    'name' => 'Назначенные тесты',
                    'url' => USite::getModurl() . '/for'
                ));
                $html = $this->loadView($this->action);
                break;

            case 'editmytest':
                $this->doAction($this->action, (array)$this->vars['id']);
                $html = $this->loadView('newmytest');
                break;

            case 'editmyquestion':
                $this->doAction($this->action,
                    array($this->vars['in_tid'], $this->vars['id']));
                $html = $this->loadView('newmyquestion');
                break;

            case 'editfortest':
                UAppBuilder::editBreadcrumpItem(array(
                    'name' => 'Назначенные тесты',
                    'url' => USite::getModurl() . '/for'
                ));
                $this->doAction($this->action, (array)$this->vars['id']);
                $html = $this->loadView('newfor');
                break;

            // for ajax
            case 'newtype':
                $html = $this->loadView('answer_' . $this->vars['qtype']);
                echo $html;
                exit;
                break;

            // for ajax
            case 'delanswer':
                $this->doAction($this->action,
                    array($this->vars['tid'], $this->vars['qid'], $this->vars['id']));
                echo $result;
                exit;
                break;

            case 'delquestion':
                $this->doAction($this->action,
                    array($this->vars['tid'], $this->vars['qid']));
                break;

            case 'delete':
                $this->doAction($this->action,
                    array($this->vars['type'], $this->vars['id']));
                break;

            case 'answerdisplay':
                $this->doAction($this->action, $this->actionArgs);
                $html = $this->loadView('answer_' . $this->actionArgs[0]);
                break;*/

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

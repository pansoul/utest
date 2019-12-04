<?php

namespace UTest\Components;

use UTest\Kernel\Site;

class PrepodMaterialsController extends \UTest\Kernel\Component\Controller
{
    protected $routeMap = array(
        'title' => 'Материал',
        'add_breadcrumb' => true,
        'action_main' => 'my',
        'actions_params' => array(
            '/my' => [
                'action' => 'my'
            ],
            '/my/<subject_code>' => [
                'action' => 'my_material',
                'title' => 'Список документов',
                'add_breadcrumb' => true
            ],
            '/my/<subject_code>/new' => [
                'action' => 'my_new',
                'title' => 'Загрузка нового материала',
                'add_breadcrumb' => true
            ],
            '/my/<subject_code>/edit/<id>' => [
                'action' => 'my_edit',
                'title' => 'Редактирование материала',
                'add_breadcrumb' => true
            ],

            '/for' => [
                'action' => 'for',
                'title' => 'Материал для групп',
                'add_breadcrumb' => true
            ],
            '/for/<group_code>' => [
                'action' => 'for_subject',
                'title' => 'По дисциплине',
                'add_breadcrumb' => true
            ],
            '/for/<group_code>/<subject_code>' => [
                'action' => 'for_material',
                'title' => 'Список документов',
                'add_breadcrumb' => true
            ],
            '/for/<group_code>/<subject_code>/new' => [
                'action' => 'for_new',
                'title' => 'Поделиться материалом',
                'add_breadcrumb' => true
            ],
            '/for/<group_code>/<subject_code>/newcomment' => [
                'action' => 'for_new_comment',
                'title' => 'Создание нового комментария',
                'add_breadcrumb' => true
            ],
            '/for/<group_code>/<subject_code>/editcomment/<id>' => [
                'action' => 'for_edit_comment',
                'title' => 'Редактирование комментария',
                'add_breadcrumb' => true
            ],

            '/delete/<type>/<id>' => [
                'action' => 'delete',
            ],
        ),
        'vars_rules' => array(
            'subject_code' => '[-_a-zA-Z0-9]',
            'group_code' => '[-_a-zA-Z0-9]',
            'id' => '[0-9]',
            'type' => '[a-zA-Z]'
        )
    );

    public function run()
    {
        // Если пришёл запрос на скачивание файла
        if ($this->model->_GET['download']) {
            $this->model->fileDownload($this->model->_GET['download']);
        }

        // Данные для построения табов
        $arTabs = array(
            'my' => array(
                'name' => 'Мой материал',
                'href' => Site::getModurl() . '/my'
            ),
            'for' => array(
                'name' => 'Материал для групп',
                'href' => Site::getModurl() . '/for'
            )
        );
        $exploded = explode('/', Site::getModParamsRow(), 3);
        $tabSelected = $exploded[1] ? $exploded[1] : 'my';

        switch ($this->action) {
            case 'my_material':
                $this->doAction($this->action, $this->getVars('subject_code'));
                $html = $this->loadView($this->action);
                break;

            case 'my_edit':
                $this->doAction($this->action, $this->getVars('id'));
                $html = $this->loadView('my_new');
                break;

            case 'for_subject':
                $this->doAction($this->action, $this->getVars('group_code'));
                $html = $this->loadView($this->action);
                break;

            case 'for_material':
                $this->doAction($this->action, $this->getVars(['group_code', 'subject_code']));
                $html = $this->loadView($this->action);
                break;

            case 'for_edit_comment':
                $this->doAction($this->action, $this->getVars(['id']));
                $html = $this->loadView('for_new_comment');
                break;

            case 'delete':
                $this->doAction($this->action, $this->getVars(['type', 'id']));
                break;

            default:
                $this->doAction($this->action);
                $html = $this->loadView($this->action);
                break;
        }

        $tabs = $this->loadView('tabs', [
            'tabs' => $arTabs,
            'selected' => $tabSelected
        ]);

        $this->putContent($tabs);
        $this->putContent($html);
    }
}
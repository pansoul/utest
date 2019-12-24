<?php

namespace UTest\Components;

class StudentMaterialsController extends \UTest\Kernel\Component\Controller
{
    protected $routeMap = array(
        'title' => 'Материал по дисциплинам',
        'add_breadcrumb' => true,
        'action_main' => 'subjects',
        'actions_params' => array(
            '/<subject_code>' => [
                'action' => 'materials',
                'title' => 'Список документов',
                'add_breadcrumb' => true
            ]
        ),
        'vars_rules' => array(
            'subject_code' => '[-_a-zA-Z0-9]'
        )
    );

    public function run()
    {
        // Если пришёл запрос на скачивание файла
        if ($this->model->_GET['download']) {
            $this->model->fileDownload($this->model->_GET['download']);
        }

        switch ($this->action) {
            case 'materials':
                $this->doAction($this->action, $this->getVars('subject_code'));
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

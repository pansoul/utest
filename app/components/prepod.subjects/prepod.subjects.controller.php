<?php

namespace UTest\Components;

class PrepodSubjectsController extends \UTest\Kernel\Component\Controller
{
    protected $routeMap = array(
        'title' => 'Мои дисциплины',
        'add_breadcrumb' => true,
        'action_main' => 'subject',
        'actions_params' => array(
            '/newsubject' => [
                'action' => 'newSubject',
                'title' => 'Создание новой дисциплины',
                'add_breadcrumb' => true
            ],
            '/edit/<id>' => [
                'action' => 'edit',
                'title' => 'Редактирование дисциплины',
                'add_breadcrumb' => true
            ],
            '/delete/<id>' => [
                'action' => 'delete',
            ],
        ),
        'vars_rules' => array(
            'id' => '[0-9]'
        )
    );

    public function run()
    {
        $html = '';

        switch ($this->action) {
            case 'edit':
                $this->doAction($this->action, $this->getVars('id'));
                $html = $this->loadView('newsubject');
                break;

            case 'delete':
                $this->doAction($this->action, $this->getVars('id'));
                break;

            default:
                $this->doAction($this->action);
                $html = $this->loadView($this->action);
                break;
        }

        $this->putContent($html);
    }

}

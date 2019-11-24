<?php

namespace UTest\Components;

class AdminPrepodsController extends \UTest\Kernel\Component\Controller
{
    protected $routeMap = array(
        'title' => 'Преподаватели',
        'add_breadcrumb' => true,
        'action_main' => 'prepod',
        'actions_params' => array(
            '/newprepod' => [
                'action' => 'newPrepod',
                'title' => 'Создание нового преподавателя',
                'add_breadcrumb' => true
            ],
            '/edit/<id>' => [
                'action' => 'edit',
                'title' => 'Редактирование преподавателя',
                'add_breadcrumb' => true
            ],
            '/delete/<id>' => [
                'action' => 'delete',
            ],
        ),
        'vars_rules' => array(
            'id' => '[0-9]',
        )
    );

    public function run()
    {
        $html = '';

        switch ($this->action) {
            case 'edit':
                $this->doAction($this->action, $this->getVars('id'));
                $html = $this->loadView('newprepod');
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
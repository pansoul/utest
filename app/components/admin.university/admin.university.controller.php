<?php

namespace UTest\Components;

use UTest\Kernel\Site;

class AdminUniversityController extends \UTest\Kernel\Component\Controller
{
    protected $routeMap = [
        'title' => 'Факультеты',
        'add_breadcrumb' => true,
        'action_main' => 'faculty',
        'action_default' => 'index',
        'actions_params' => [
            '/newfaculty' => [
                'action' => 'newFaculty',
                'title' => 'Новый факультет',
                'add_breadcrumb' => true,
            ],
            '/editfaculty/<id>' => [
                'action' => 'editFaculty',
                'title' => 'Создание нового факультета',
                'add_breadcrumb' => true,
            ],

            '/<faculty_code>' => [
                'action' => 'speciality',
                'title' => 'Специальности',
                'add_breadcrumb' => true,
            ],
            '/<faculty_code>/newspeciality' => [
                'action' => 'newSpeciality',
                'title' => 'Создание новой специальности',
                'add_breadcrumb' => true,
            ],
            '/<faculty_code>/editspeciality/<id>' => [
                'action' => 'editSpeciality',
                'title' => 'Редактирование специальности',
                'add_breadcrumb' => true,
            ],

            '/delete/<type>/<id>' => [
                'action' => 'delete'
            ]
        ],
        'vars_rules' => [
            'faculty_code' => '[-_a-zA-Z0-9]',
            'in' => '[-_a-zA-Z0-9]',
            'id' => '[0-9]',
            'type' => '[a-zA-Z]'
        ],
    ];

    public function run()
    {
        $html = '';

        switch ($this->action) {
            case 'faculty':
            case 'speciality':
                $this->doAction($this->action, $this->getVars('faculty_code'));
                $html = $this->loadView($this->getVars('faculty_code') ? 'speciality' : 'faculty');
                break;

            case 'editFaculty':
                $this->doAction($this->action, $this->getVars('id'));
                $html = $this->loadView('newfaculty');
                break;

            case 'editSpeciality':
                $this->doAction($this->action, $this->getVars('id'));
                $html = $this->loadView('newspeciality');
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
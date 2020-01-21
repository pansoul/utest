<?php

namespace UTest\Components;

class AdminSettingController extends \UTest\Kernel\Component\Controller
{
    protected $routeMap = array(
        'title' => 'Информация о вузе',
        'add_breadcrumb' => true,
    );

    public function run()
    {
        switch ($this->action) {
            case 'univer':
                $this->doAction($this->action, $this->actionArgs);
                $html = $this->loadView('univer_' . $this->actionArgs[0]);
                break;

            default:
                $this->doAction($this->action);
                $html = $this->loadView($this->action);
                break;
        }

        $this->putContent($html);
    }
}
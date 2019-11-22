<?php

namespace UTest\Components;

class AdminProfileController extends \UTest\Kernel\Component\Controller
{
    protected $routeMap = array(
        'title' => 'Изменение пароля',
        'add_breadcrumb' => true
    );

    public function run()
    {           
        $this->doAction($this->action);
        $html = $this->loadView();
        $this->putContent($html);
    }
}
<?php

namespace UTest\Components;

class IndexController extends \UTest\Kernel\ComponentController
{
    protected $routeMap = array(
        'setTitle' => 'Добро пожаловать'
    );

    public function run()
    {
        $this->doAction($this->action);
        $html = $this->loadView('mainform');
        $this->putContent($html);
    }
}

<?php

namespace UTest\Components;

class IndexController extends \UTest\Kernel\Component\Controller
{
    protected $routeMap = array(
        'title' => 'Добро пожаловать'
    );

    public function run()
    {
        $this->doAction($this->action);
        $html = $this->loadView('mainform');
        $this->putContent($html);
    }
}

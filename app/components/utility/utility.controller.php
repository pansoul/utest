<?php

namespace UTest\Components;

use UTest\Kernel\Site;

class UtilityController extends \UTest\Kernel\ComponentController
{
    public function run()
    {
        $html = '';

        switch ($this->action) {
            case 'menu':
                $this->doAction($this->action, array(Site::getGroup()));
                $html = $this->loadView('menu');
                break;

            case 'panel':
                $this->doAction($this->action);
                $html = $this->loadView('panel');
                break;

            case 'univer':
                $this->doAction($this->action, $this->actionArgs);
                $html = $this->loadView($this->actionArgs[0]);
                break;

            case 'breadcrumb':
                $this->doAction($this->action, $this->actionArgs);
                $html = $this->loadView('breadcrumb');
                break;

            case 'pastable':
                $this->doAction($this->action, $this->actionArgs);
                $html = $this->loadView('pastable');
                break;

            case 'tabs':
                $this->doAction($this->action, $this->actionArgs);
                $html = $this->loadView('tabs');
                break;

            case 'testanswer':
                $this->doAction($this->action, $this->actionArgs);
                $html = $this->loadView('test_answer_' . $this->actionArgs[0]);
                break;

            case 'testresult':
                $this->doAction($this->action, $this->actionArgs);
                $html = $this->loadView('test_result');
                break;
        }

        $this->putContent($html);
    }
}

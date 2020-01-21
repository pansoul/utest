<?php

namespace UTest\Components;

use UTest\Kernel\User\User;
use UTest\Kernel\Base;

class UtilityModel extends \UTest\Kernel\Component\Model
{
    const RESULT_MODE_SHORT = 'short';
    const RESULT_MODE_DETAIL = 'detail';
    const RESULT_MODE_FULL = 'full';

    public function menuAction($role)
    {
        $menu = Base::getConfig('menus > ' . $role);
//        if (!$menu) {
//            throw new AppException("Меню для роли {$role} не создано");
//        }
        $this->setData($menu);
    }

    public function panelAction()
    {
        if (isset($this->_GET['logout']) && $this->_GET['logout'] == 'Y') {
            User::logout();
        }
    }

    public function breadcrumbAction($arr)
    {
        $this->setData($arr);
    }

    public function pastableAction($arr)
    {
        $this->setData($arr);
    }

    public function tabsAction($arr, $selected)
    {
        $this->setData(array(
            'tabs' => $arr,
            'selected' => $selected
        ));
    }

    public function testAnswerAction($type, $a, $r)
    {
        $this->setData(array(
            'answer' => $a,
            'right' => $r
        ));
    }

    public function testResultAction(\UTest\Kernel\Test\Result $result, $mode)
    {
        $this->setData([
            'result' => $result,
            'mode' => $mode
        ]);
    }
}

<?php

namespace UTest\Components;

use UTest\Kernel\User\User;
use UTest\Kernel\Errors\AppException;
use UTest\Kernel\Base;
use UTest\Kernel\DB;

class UtilityModel extends \UTest\Kernel\Component\Model
{
    const UNIVER_DATA_ID = 1;

    public function menuAction($role = '')
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

    public function univerAction($field)
    {
        $data = DB::table(TABLE_UNIVER_DATA)->find(self::UNIVER_DATA_ID);
        $this->setData(html_entity_decode($data[$field]));
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

    public function testResultAction($res)
    {
        return $this->returnResult($res);
    }

}

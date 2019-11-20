<?php

namespace UTest\Components;

use UTest\Kernel\User\User;
use UTest\Kernel\Errors\AppException;

class UtilityModel extends \UTest\Kernel\ComponentModel
{
    const UNIVER_DATA_ID = 1;

    public function menuAction($node)
    {
        if ($node === null) {
            throw new AppException('Не указано имя меню для вывода');
        }

        $generalMenu = require APP_CONFIG_PATH . '/menus.php';
        $curMenu = @$generalMenu[$node];
        $this->setData($curMenu);
    }

    public function panelAction()
    {
        if (isset($this->_GET['logout']) && $this->_GET['logout'] == 'Y') {
            User::logout();
        }
    }

    public function univerAction($field)
    {
        if ($field == 'univer_name') {
            $field = 'name';
        } elseif ($field == 'univer_fullname') {
            $field = 'fullname';
        }
        $data = \R::load(TABLE_UNIVER_DATA, self::UNIVER_DATA_ID);
        $this->setData($data->{$field});
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

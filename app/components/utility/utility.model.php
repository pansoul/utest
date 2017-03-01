<?php

class UtilityModel extends UModel {
    
    const ID_U_DATA = 1; 

    public function menuAction($node)
    {
        if ($node === null) {
            throw new UAppException('Не указано имя меню для вывода');
        }

        $generalMenu = include APP_CONFIG_PATH . '/menus.php';
        $curMenu = @$generalMenu[$node];

        return $this->returnResult($curMenu);
    }

    public function panelAction()
    {
        if (isset($this->request->_GET['logout']) &&  $this->request->_GET['logout'] == 'Y') {
            UUser::logout();
        }
        return $this->returnResult();
    }

    public function univerAction($field)
    {
        if ($field == 'univer_name') {
            $field = 'name';
        } elseif ($field == 'univer_fullname') {
            $field = 'fullname';
        }
        $data = R::load(TABLE_UNIVER_DATA, self::ID_U_DATA);
        return $this->returnResult($data->$field);
    }
    
    public function breadcrumbAction($arr)
    {
        return $this->returnResult($arr);
    }
    
    public function pastableAction($arr)
    {
        return $this->returnResult($arr);
    }
    
    public function tabsAction($arr, $selected)
    {
        return $this->returnResult(array(
            'tabs' => $arr,
            'selected' => $selected
        ));
    }
    
    public function testAnswerAction($type, $a, $r)
    {
        return $this->returnResult(array(
            'answer' => $a,
            'right' => $r
        ));
    }
    
    public function testResultAction($res)
    {
        return $this->returnResult($res);
    }

}

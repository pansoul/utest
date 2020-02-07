<?php

class UtilityModel extends UModel {
    
    const ID_U_DATA = 1;
    private $table_univer_data = 'u_univer_data';    

    public function menuAction($node)
    {
        if ($node === null)
            throw new UAppException('Не указано имя меню для вывода');

        $generalMenu = include APP_CONFIG_PATH . '/menus.php';
        $curMenu = @$generalMenu[$node];

        return $this->returnResult($curMenu);
    }

    public function panelAction()
    {
        if (isset($this->request->_get['logout']) &&  $this->request->_get['logout'] == 'Y')
            UUser::logout();
        return $this->returnResult();
    }

    public function univerAction($field)
    {
        if ($field == 'univer_name')
            $field = 'name';
        elseif ($field == 'univer_fullname')
            $field = 'fullname';
        $data = R::load($this->table_univer_data, self::ID_U_DATA);
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
    
    public function answerDisplayAction($type, $v, $a, $r)
    {
        return $this->returnResult(array(
            'form_question' => $v,
            'form_answer' => $a,
            'form_right' => $r
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

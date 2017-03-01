<?php

class Admin extends UUser {
    
    const VIP_ID = 1;
    
    private $arFields = array(
        'role' => array(
            'for_action' => array('add'),
            'required' => array('add')
        ),
        'password' => array(
            'for_action' => array('add', 'edit'),
            'required' => array('add')
        ),
        'name' => array(
            'for_action' => array('add', 'edit'),
            'required' => array('add', 'edit')
        ),
        'last_name' => array(
            'for_action' => array('add', 'edit'),
            'required' => array('add', 'edit')
        ),
        'surname' => array(
            'for_action' => array('add', 'edit'),            
        ),
        'phone' => array(
            'for_action' => array('add', 'edit'),            
        ),
        'email' => array(
            'for_action' => array('add', 'edit'),            
        ),
        'group_id' => array(
            'for_action' => array('add', 'edit'),            
        ),
        'post' => array(
            'for_action' => array('add', 'edit'),            
        ),
    );
    
    private $arAvailableAdd = array(
        'role',
        'password',
        'name',
        'last_name',
        'surname',
        'phone',
        'email',
        'group_id',
        'post'
    );    
    private $arRequiredAdd = array(
        'role',
        'password',
        'name',
        'last_name'
    );
    
    private $arAvailableEdit = array(
        'password',
        'name',
        'last_name',
        'surname',
        'phone',
        'email',
        'group_id',
        'post'
    );    
    private $arRequiredEdit = array(        
        'name',
        'last_name'
    );

    public function __construct()
    {
        //
    }

    public function add(array $arFields = array())
    {
        $_e = array();
        $_translate = array(
            'role' => 'Роль пользователя',
            'name' => 'Имя пользователя',
            'last_name' => 'Фамилия пользователя',
            'password' => 'Пароль',
            //'group_id' => 'Группа'
        );
        
        foreach ($arFields as $k => $v)
        {
            if (!in_array($k, $this->arAvailableAdd) || !is_string($k)) {
                unset($arFields[$k]);
            }
        }
        
        if (empty($arFields)) {
            $_e[] = 'Входной массив параметров для создания пользователя пуст';
            self::$last_errors = $_e;
            return false;
        }
        
        foreach ($this->arRequiredAdd as $oneRequired)
        {
            if (!key_exists($oneRequired, $arFields) || empty($arFields[$oneRequired])) {
                $_e[] = "Заполните поле '{$_translate[$oneRequired]}'";
            } elseif ($oneRequired == 'role') {                
                $isset = R::count(self::$table_roles, "`type` = ?", array('prepod'));                
                if (!$isset) {
                    $_e[] = "Роль '{$arFields[$oneRequired]}' не найдена в системе";
                }
            }
        }
        if (!empty($_e)) {
            self::$last_errors = $_e;
            return false;
        }
        
        if (empty($arFields['group_id'])) {
            $arFields['group_id'] = null;  
        }
        
        $newUser = R::dispense(self::$table);
        foreach ($arFields as $k => $v)
        {
            $newUser->$k = $v;
        }        
        $newUser->salt = $this->generateSalt();
        $newUser->password = md5(sha1($arFields['password']) . $newUser->salt);
        $id = R::store($newUser);
        $rootRole = self::getRootGroup($arFields['role']);
        switch ($rootRole)
        {
            case 'admin':
                $logname = 'admin';
                break;
            
            case 'prepod':
                $logname = 'prepod';
                break;
            
            default :
                $logname = 'student';
        }
        $login = $logname . str_pad($id, 2, '0', STR_PAD_LEFT);
        $newUser->login = $login;
        R::store($newUser);
        
        return array(
            'id' => $id,
            'login' => $login,
            'password' => $arFields['password'],
            'fullname' => $arFields['last_name'] . ' ' . $arFields['name']
        );
    }
    
    public function edit(array $arFields = array(), $uid = null)
    {        
        $_e = array();
        $_translate = array(            
            'name' => 'Имя пользователя',
            'last_name' => 'Фамилия пользователя'
            //'group_id' => 'Группа'
        );
        
        $uid = is_null($uid) ? $this->getUID() : $uid;
        
        $curUser = R::load(self::$table, $uid);
        if (!$curUser) {
            $_e[] = "Пользователя с Id = $uid не существует";
        }
        
        if (!empty($_e)) {
            self::$last_errors = $_e;
            return false;
        }
        
        foreach ($arFields as $k => $v)
        {
            if (!in_array($k, $this->arAvailableEdit) || !is_string($k)) {
                unset($arFields[$k]);
            }
        }
        
        if (empty($arFields)) {
            $_e[] = 'Входной массив параметров для редактирования пользователя пуст';
            self::$last_errors = $_e;
            return false;
        }
        
        foreach ($this->arRequiredEdit as $oneRequired)
        {
            if (isset($arFields[$oneRequired]) && empty($arFields[$oneRequired])) {
                $_e[] = "Заполните поле '{$_translate[$oneRequired]}'";
            }
        }
        if (!empty($_e)) {
            self::$last_errors = $_e;
            return false;
        }
        foreach ($arFields as $k => $v)
        {
            $curUser->$k = $v;
        }        
        // новый пароль
        if (!empty($arFields['password'])) {
            $curUser->salt = $this->generateSalt();
            $curUser->password = md5(sha1($arFields['password']) . $curUser->salt);
        } else {
            unset($curUser->password);
        }
        R::store($curUser);
        
        return array(
            'id' => $curUser['id'],
            'login' => $curUser['login'],
            'password' => $arFields['password'],
            'fullname' => $curUser['last_name'] . ' ' . $curUser['name']
        );
    }

    public function delete($uid)
    {
        $_e = array();
        
        $bean = R::load(self::$table, $uid);
        if (!$bean) {
            $_e[] = "Пользователя с Id = $uid не существует";        
        } elseif ($bean['id'] == self::VIP_ID) {
            $_e[] = "Невозможно удалить пользователя с Id = " . self::VIP_ID;        
        }
        
        if (!empty($_e)) {
            self::$last_errors = $_e;
            return false;
        }
        
        R::trash($bean);
        return true;
    }
    
}


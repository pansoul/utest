<?php

namespace UTest\Kernel\User\Roles;

use \R;
use UTest\Kernel\Utilities;

class Admin extends \UTest\Kernel\User\User
{
    const ADMIN_ID = 1;
    const FIELDS_GROUP_ADD = 'add';
    const FIELDS_GROUP_EDIT = 'edit';
    const FIELDS_TYPE_AVAILABLE = 'available';
    const FIELDS_TYPE_REQUIRED = 'required';

    private $arFields = [
        'role' => [
            'name' => 'Роль пользователя',
            self::FIELDS_TYPE_AVAILABLE => [self::FIELDS_GROUP_ADD],
            self::FIELDS_TYPE_REQUIRED => [self::FIELDS_GROUP_ADD]
        ],
        'password' => [
            'name' => 'Пароль',
            self::FIELDS_TYPE_AVAILABLE => [self::FIELDS_GROUP_ADD, self::FIELDS_GROUP_EDIT],
            self::FIELDS_TYPE_REQUIRED => [self::FIELDS_GROUP_ADD]
        ],
        'name' => [
            'name' => 'Имя',
            self::FIELDS_TYPE_AVAILABLE => [self::FIELDS_GROUP_ADD, self::FIELDS_GROUP_EDIT],
            self::FIELDS_TYPE_REQUIRED => [self::FIELDS_GROUP_ADD, self::FIELDS_GROUP_EDIT]
        ],
        'last_name' => [
            'name' => 'Фамилия',
            self::FIELDS_TYPE_AVAILABLE => [self::FIELDS_GROUP_ADD, self::FIELDS_GROUP_EDIT],
            self::FIELDS_TYPE_REQUIRED => [self::FIELDS_GROUP_ADD, self::FIELDS_GROUP_EDIT]
        ],
        'surname' => [
            'name' => 'Отчество',
            self::FIELDS_TYPE_AVAILABLE => [self::FIELDS_GROUP_ADD, self::FIELDS_GROUP_EDIT],
        ],
        'phone' => [
            'name' => 'Пароль',
            self::FIELDS_TYPE_AVAILABLE => [self::FIELDS_GROUP_ADD, self::FIELDS_GROUP_EDIT],
        ],
        'email' => [
            'name' => 'E-mail',
            self::FIELDS_TYPE_AVAILABLE => [self::FIELDS_GROUP_ADD, self::FIELDS_GROUP_EDIT],
        ],
        'group_id' => [
            'name' => 'Группа',
            self::FIELDS_TYPE_AVAILABLE => [self::FIELDS_GROUP_ADD, self::FIELDS_GROUP_EDIT],
        ],
        'post' => [
            'name' => 'Должность',
            self::FIELDS_TYPE_AVAILABLE => [self::FIELDS_GROUP_ADD, self::FIELDS_GROUP_EDIT],
        ],
    ];

    public function getGroupFields($groups = null, $types = self::FIELDS_TYPE_AVAILABLE)
    {
        if (empty($groups) || empty($types)) {
            return array_keys($this->arFields);
        }

        $groups = (array) $groups;
        $types = (array) $types;
        $arFields = array_filter($this->arFields, function($item) use ($groups, $types) {
            foreach ($types as $type) {
                if (array_diff($groups, (array) @$item[$type])) {
                    return false;
                }
            }
            return true;
        });

        return array_keys($arFields);
    }

    // @todo
    public function add($arFields = array())
    {
        $arFields = $this->checkFields($arFields, self::FIELDS_GROUP_ADD);
        if ($arFields === false) {
            return false;
        }

        if (empty($arFields['group_id'])) {
            $arFields['group_id'] = null;
        }

        $rootRole = self::getRootGroup($arFields['role']);
        if (!$rootRole) {
            return false;
        }

        $newUser = R::dispense(TABLE_USER);
        foreach ($arFields as $k => $v) {
            $newUser->{$k} = $v;
        }
        $newUser->salt = Utilities::generateSalt();
        $newUser->password = md5(sha1($arFields['password']) . $newUser->salt);
        $id = R::store($newUser);
        // @todo
        switch ($rootRole) {
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

    public function edit($arFields = array(), $uid = null)
    {
        $uid = intval($uid) ? intval($uid) : $this->getUID();
        $user = self::getById($uid);

        if (!$user) {
            return false;
        }

        $arFields = $this->checkFields($arFields, self::FIELDS_GROUP_EDIT);
        if ($arFields === false) {
            return false;
        }

        foreach ($arFields as $k => $v) {
            $user->{$k} = $v;
        }
        // новый пароль
        if (!empty($arFields['password'])) {
            $user->salt = Utilities::generateSalt();
            $user->password = md5(sha1($arFields['password']) . $user->salt);
        } else {
            unset($user->password);
        }
        R::store($user);

        return array(
            'id' => $user['id'],
            'login' => $user['login'],
            'password' => $arFields['password'],
            'fullname' => $user['last_name'] . ' ' . $user['name']
        );
    }

    public function delete($uid)
    {
        $e = array();

        $user = self::getById($uid);
        if (!$user) {
            return false;
        } elseif ($user['id'] == self::ADMIN_ID) {
            $e[] = "Невозможно удалить пользователя с Id = " . self::ADMIN_ID;
            self::$last_errors = $e;
            return false;
        }

        R::trash($user);
        return true;
    }

    private function checkFields($arFields = [], $group = null)
    {
        $e = [];
        $arAvailableFields = $this->getGroupFields($group);
        $arRequiredFields = $this->getGroupFields($group, [self::FIELDS_TYPE_AVAILABLE, self::FIELDS_TYPE_REQUIRED]);

        $arFields = array_filter($arFields, function($key) use ($arAvailableFields) {
            return in_array($key, $arAvailableFields);
        }, ARRAY_FILTER_USE_KEY);

        if (empty($arFields)) {
            $e[] = 'Входной массив параметров для редактирования пользователя пуст';
            self::$last_errors = $e;
            return false;
        }

        if ($group == self::FIELDS_GROUP_EDIT) {
            foreach ($arRequiredFields as $field) {
                if (isset($arFields[$field]) && empty($arFields[$field])) {
                    $e[] = "Заполните поле '{$this->arFields[$field]['name']}'";
                }
            }
        } else {
            foreach ($arRequiredFields as $field) {
                if (!key_exists($field, $arFields) || empty($arFields[$field])) {
                    $e[] = "Заполните поле '{$this->arFields[$field]['name']}'";
                }
            }
        }


        if (!empty($e)) {
            self::$last_errors = $e;
            return false;
        }

        return $arFields;
    }
}
<?php

namespace UTest\Kernel\User\Roles;

use UTest\Kernel\Utilities;
use UTest\Kernel\DB;
use UTest\Kernel\Traits\FieldsValidateTraitHelper;

class Admin extends \UTest\Kernel\User\User
{
    use \UTest\Kernel\Traits\FieldsValidateTrait;

    const ROLE = 'admin';

    private function fieldsMap()
    {
        return [
            'role' => [
                FieldsValidateTraitHelper::_NAME => 'Роль пользователя',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD
                ],
                FieldsValidateTraitHelper::_REQUIRED => true,
            ],
            'password' => [
                FieldsValidateTraitHelper::_NAME => 'Пароль',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ],
                FieldsValidateTraitHelper::_REQUIRED => function ($v, $group) {
                    return in_array(FieldsValidateTraitHelper::_ADD, $group);
                },
            ],
            FieldsValidateTraitHelper::_NAME => [
                FieldsValidateTraitHelper::_NAME => 'Имя',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ],
                FieldsValidateTraitHelper::_REQUIRED => true,
            ],
            'last_name' => [
                FieldsValidateTraitHelper::_NAME => 'Фамилия',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ],
                FieldsValidateTraitHelper::_REQUIRED => true,
            ],
            'surname' => [
                FieldsValidateTraitHelper::_NAME => 'Отчество',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ],
            ],
            'phone' => [
                FieldsValidateTraitHelper::_NAME => 'Пароль',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ],
            ],
            'email' => [
                FieldsValidateTraitHelper::_NAME => 'E-mail',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ],
            ],
            'group_id' => [
                FieldsValidateTraitHelper::_NAME => 'Группа',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ],
                FieldsValidateTraitHelper::_REQUIRED => function ($v, $group) {
                    return $v['role'] == Student::ROLE;
                },
                // @todo
                FieldsValidateTraitHelper::_VALIDATE => [
                    'type' => 'integer',
                    'limit' => 11,
                    'link' => [TABLE_UNIVER_GROUP, 'id']
                ]
            ],
            'post' => [
                FieldsValidateTraitHelper::_NAME => 'Должность',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ]
            ],
        ];
    }

    public function add($arFields = array())
    {
        self::clearLastErrors();
        $arFields = $this->checkFields($this->fieldsMap(), $arFields, FieldsValidateTraitHelper::_ADD, self::$lastErrors);

        // @todo сделать также доп. проверки на другие связанные значения
        if (!self::isSysRoleExists($arFields['role'])) {
            self::setLastErrors("Роль {$arFields['role']} не существует");
        }

        if (self::hasLastErrors()) {
            return false;
        }

        $password = $arFields['password'];
        $arFields['salt'] = Utilities::generateSalt();
        $arFields['password'] = md5(sha1($password) . $arFields['salt']);
        $arFields['role'] = strtolower($arFields['role']);
        $arFields['group_id'] = $arFields['group_id'] ? intval($arFields['group_id']) : null;

        $id = DB::table(TABLE_USER)->insertGetId($arFields);

        $login = $arFields['role'] . str_pad($id, 2, '0', STR_PAD_LEFT);
        DB::table(TABLE_USER)->where('id', '=', $id)->update(['login' => $login]);

        return array(
            'id' => $id,
            'login' => $login,
            'password' => $password,
            'fullname' => $arFields['last_name'] . ' ' . $arFields['name']
        );
    }

    public function edit($arFields = array(), $uid = null)
    {
        self::clearLastErrors();
        $uid = intval($uid) ? intval($uid) : $this->getUID();
        $user = self::getById($uid);

        if (!$user) {
            return false;
        }

        $arFields = $this->checkFields($this->fieldsMap(), $arFields, FieldsValidateTraitHelper::_EDIT, self::$lastErrors);

        if (self::hasLastErrors()) {
            return false;
        }

        $arFields['group_id'] = $arFields['group_id'] ? intval($arFields['group_id']) : null;

        // новый пароль
        if (!empty($arFields['password'])) {
            $password = $arFields['password'];
            $arFields['salt'] = Utilities::generateSalt();
            $arFields['password'] = md5(sha1($password) . $arFields['salt']);
        } else {
            unset($arFields['password']);
        }
        DB::table(TABLE_USER)->where('id', '=', $uid)->update($arFields);

        return array(
            'id' => $uid,
            'login' => $user['login'],
            'password' => $password,
            'fullname' => $user['last_name'] . ' ' . $user['name']
        );
    }

    public function delete($uid)
    {
        self::clearLastErrors();

        $user = self::getById($uid);
        if (!$user) {
            return false;
        } elseif ($user['id'] == self::ADMIN_ID) {
            self::setLastErrors("Невозможно удалить пользователя с Id = " . self::ADMIN_ID);
            return false;
        }

        DB::table(TABLE_USER)->delete($user['id']);
        return true;
    }
}
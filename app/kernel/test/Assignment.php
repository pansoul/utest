<?php

namespace UTest\Kernel\Test;

use UTest\Kernel\DB;
use UTest\Kernel\User\User;
use UTest\Kernel\Traits\FieldsValidateTraitHelper;

class Assignment
{
    use \UTest\Kernel\Traits\ErrorsManageTrait;
    use \UTest\Kernel\Traits\FieldsValidateTrait;

    const CHECK_UID = 'uid';
    const CHECK_ATID = 'atid';

    private $uid = 0; // {userId} - Id пользователя (автор назначенного теста)
    private $atid = 0; // {assignedTestId} - Id назначенного теста

    private $assignData = [];
    private $baseList = [];

    public function __construct($uid = 0, $atid = 0)
    {
        if (!User::getById($uid)) {
            $this->setErrors('Id автора назначенного теста указан неверно или не существует');
        } else {
            $this->uid = $uid;
        }

        if ($atid) {
            $this->loadAssign($atid);
        }
    }

    private function assignFieldsMap()
    {
        return [
            'title' => [
                FieldsValidateTraitHelper::_NAME => 'Название теста',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
            'user_id' => [
                FieldsValidateTraitHelper::_NAME => 'Привязка к пользователю',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
            'subject_id' => [
                FieldsValidateTraitHelper::_NAME => 'Привязка к предмету',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
            'group_id' => [
                FieldsValidateTraitHelper::_NAME => 'Привязка к группе',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
            'test_id' => [
                FieldsValidateTraitHelper::_NAME => 'Основа теста',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
            'is_mixing' => [
                FieldsValidateTraitHelper::_NAME => 'Перемешивание',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ]
            ],
            'is_show_true' => [
                FieldsValidateTraitHelper::_NAME => 'Показывать верные варианты',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ]
            ],
            'count_q' => [
                FieldsValidateTraitHelper::_NAME => 'Количество вопросов',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD
                ]
            ],
            'time' => [
                FieldsValidateTraitHelper::_NAME => 'Ограничение по времени',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ]
            ],
            'date' => [
                FieldsValidateTraitHelper::_NAME => 'Дата',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
        ];
    }

    public function loadAssign($id, $loadBaseList = false)
    {
        if ($id > 0 && $id == $this->atid) {
            return $this->assignData;
        }

        $this->clearErrors();
        $this->clearAssidnedTestData();

        if (!$this->checkPermissions(self::CHECK_UID)) {
            return false;
        }

        $res = DB::table(TABLE_STUDENT_TEST)->where(['id' => $id, 'user_id' => $this->uid])->first();
        if (!$res) {
            $this->setErrors('Назначенный тест не найден');
        } else {
            $this->assignData = $res;
            $this->atid = $id;
            if ($loadBaseList) {
                $this->loadBase();
            }
        }

        return $this->assignData;
    }

    public function loadBaseList()
    {
        if (!$this->checkPermissions([self::CHECK_UID, self::CHECK_ATID])) {
            return false;
        }

        $this->baseList = DB::table(TABLE_TEST)->where(['id' => $this->atid, 'user_id' => $this->uid])->orderBy('title')->get();
        return true;
    }

    public function checkBase($ids = null)
    {
        $ids = (array) $ids;
        $ids = array_filter($ids, function($value){ return $value > 0; });
        if (empty($ids)) {
            $this->setErrors('Тест-основа не выбрана');
            return false;
        }

        $test = new Test($this->uid);
        foreach ($ids as $id) {
            if (!$test->loadTest($id)) {
                $this->setErrors($test->getErrors());
                return false;
            }
        }

        return true;
    }

    public function create($v = [])
    {
        $this->clearErrors();
        if (!$this->checkPermissions(self::CHECK_UID)) {
            return false;
        }

        $id = false;
        $v['user_id'] = $this->uid;
        $v = $this->checkFields($this->assignFieldsMap(), $v, FieldsValidateTraitHelper::_ADD, $this->errors);

        if ($this->checkBase($v['test_id']) && !$this->hasErrors()) {
            $id = DB::table(TABLE_STUDENT_TEST)->insertGetId($v);
            $this->loadAssign($id);
        }

        return $id;
    }

    public function edit($v = [], $id = 0)
    {
        $this->clearErrors();
        if (!$this->checkPermissions(self::CHECK_UID)) {
            return false;
        }

        $rows = false;
        $v = $this->checkFields($this->assignFieldsMap(), $v, FieldsValidateTraitHelper::_EDIT, $this->errors);

        if (!$this->hasErrors()) {
            $rows = DB::table(TABLE_STUDENT_TEST)->where(['id' => $id, 'user_id' => $this->uid])->update($v);
        }

        return $rows;
    }

    public function createOrEdit($v = [], $id = 0)
    {
        return $id ? $this->edit($v, $id) : $this->create($v);
    }

    public function delete($id = 0)
    {
        $this->clearErrors();
        if (!$this->checkPermissions(self::CHECK_UID)) {
            return false;
        }

        $rows = false;
        if (DB::table(TABLE_STUDENT_TEST)->where(['id' => $id, 'user_id' => $this->uid])->exists()) {
            $rows = DB::table(TABLE_STUDENT_TEST)->delete($id);
        }

        if ($rows && $this->atid == $id) {
            $this->clearAssidnedTestData();
        }

        return $rows;
    }

    public function getAssignData()
    {
        return $this->assignData;
    }

    public function getAssignedTestId()
    {
        return $this->atid;
    }

    /**
     * Проверяет на наличие необходимых загруженных данных
     * @param null $types
     * @return bool
     */
    private function checkPermissions($types = null)
    {
        $types = (array) $types;
        if (empty($types)) {
            return false;
        }

        $errorMessages = [
            self::CHECK_UID => 'Автор не загружен',
            self::CHECK_ATID => 'Назначенный тест не загружен'
        ];

        $result = 0;
        foreach ($types as $type) {
            $check = false;
            switch ($type) {
                case self::CHECK_UID:
                    $check = !empty($this->uid);
                    break;

                case self::CHECK_ATID:
                    $check = !empty($this->atid);
                    break;

                default:
                    continue;
            }
            if (!$check) {
                $this->setErrors($errorMessages[$type]);
            }
            $result += intval($check);
        }

        return $result == count($types);
    }

    private function clearAssidnedTestData()
    {
        $this->atid = 0;
        $this->assignData = [];
        $this->baseList = [];
    }
}
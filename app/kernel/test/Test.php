<?php

namespace UTest\Kernel\Test;

use UTest\Kernel\DB;
use UTest\Kernel\User\User;
use UTest\Kernel\Traits\FieldsValidateTraitHelper;
use UTest\Kernel\Base;

class Test
{
    use \UTest\Kernel\Traits\ErrorsManageTrait;
    use \UTest\Kernel\Traits\FieldsValidateTrait;

    const TYPE_TEST = 'test';
    const TYPE_QUESTION = 'question';
    const TYPE_ANSWER = 'answer';

    const CHECK_UID = 'uid';
    const CHECK_TID = 'tid';
    const CHECK_QID = 'qid';
    const CHECK_AID = 'aid';

    const ANSWERS_MODE_FULL = 'full';
    const ANSWERS_MODE_VARIANTS = 'variants';
    const ANSWERS_MODE_RIGHTS = 'rights';

    private $uid = 0;
    private $tid = 0;
    private $qid = 0;
    private $aid = 0;

    private $testData = [];
    private $questionData = [];
    private $answerData = [];
    private $questionsList = [];
    private $answersList = [];

    /**
     * @var \UTest\Kernel\Test\Types\AbstractType
     */
    private $qTypeEntity = null;

    public function __construct($uid = 0, $tid = 0, $qid = 0, $aid = 0)
    {
        if (!User::getById($uid)) {
            $this->setErrors('Id автора теста указан неверно или не существует');
        } else {
            $this->uid = $uid;
        }

        if ($tid) {
            $this->loadTest($tid);
        }

        if ($qid) {
            $this->loadQuestion($qid);
        }

        if ($aid) {
            $this->loadAnswer($aid);
        }
    }

    private function testFieldsMap()
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
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
        ];
    }

    private function questionFieldsMap()
    {
        return [
            'text' => [
                FieldsValidateTraitHelper::_NAME => 'Текст вопроса',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
            'type' => [
                FieldsValidateTraitHelper::_NAME => 'Тип вопроса',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
            'test_id' => [
                FieldsValidateTraitHelper::_NAME => 'Привязка к тесту',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
            'ord' => [
                FieldsValidateTraitHelper::_NAME => 'Индекс сортировки',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ]
            ],
            'parent_id' => [
                FieldsValidateTraitHelper::_NAME => '???' // @todo
            ],
        ];
    }

    public function create($v = [])
    {
        $this->clearErrors();
        if (!$this->checkPermissions(self::CHECK_UID)) {
            return false;
        }

        $id = false;
        $v['user_id'] = $this->uid;
        $v = $this->checkFields($this->testFieldsMap(), $v, FieldsValidateTraitHelper::_ADD, $this->errors);

        if (!$this->hasErrors()) {
            $id = DB::table(TABLE_TEST)->insertGetId($v);
            $this->loadTest($id);
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
        $v = $this->checkFields($this->testFieldsMap(), $v, FieldsValidateTraitHelper::_EDIT, $this->errors);

        if (!$this->hasErrors()) {
            $rows = DB::table(TABLE_TEST)->where(['id' => $id, 'user_id' => $this->uid])->update($v);
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

        $res = DB::table(TABLE_TEST)->where(['id' => $id, 'user_id' => $this->uid])->first();

        if ($res && $this->tid == $id) {
            DB::table(TABLE_TEST)->delete($id);
            $this->clearData(self::TYPE_TEST);
            return true;
        }

        return false;
    }

    public function loadTest($id = 0)
    {
        if ($id > 0 && $id == $this->tid) {
            return $this->testData;
        }

        $this->clearErrors();
        $this->clearData(self::TYPE_TEST);
        if (!$this->checkPermissions(self::CHECK_UID)) {
            return false;
        }

        $res = DB::table(TABLE_TEST)->where(['id' => $id, 'user_id' => $this->uid])->first();
        if (!$res) {
            $this->setErrors('Тест не найден');
        } else {
            $this->testData = $res;
            $this->tid = $id;
        }

        return $this->testData;
    }

    public function loadQuestionsList()
    {
        if (!$this->checkPermissions(self::CHECK_TID)) {
            return false;
        }

        $this->questionsList = DB::table(TABLE_TEST_QUESTION)->where('test_id', '=', $this->tid)->orderBy('ord')->get();
        return true;
    }

    public function loadQuestion($id = 0)
    {
        if ($id > 0 && $id == $this->qid) {
            return $this->questionData;
        }

        $this->clearErrors();
        $this->clearData(self::TYPE_QUESTION);
        if (!$this->checkPermissions(self::CHECK_TID)) {
            return false;
        }

        $res = DB::table(TABLE_TEST_QUESTION)->where(['id' => $id, 'test_id' => $this->tid])->first();
        if (!$res) {
            $this->setErrors('Вопрос не найден');
        } else {
            $this->questionData = $res;
            $this->qid = $id;
            if (!self::isQuestionTypeExists($res['type'])) {
                $this->setErrors("Тип вопроса {$res['type']} не найден");
            } else {
                $qTypeClass = self::getQuestionTypeClass($res['type']);
                $this->qTypeEntity = new $qTypeClass($this->qid);
            }
        }

        return $this->questionData;
    }

    public function deleteAnswer($id = 0)
    {
        $this->clearErrors();
        if (!$this->checkPermissions(self::CHECK_QID)) {
            return false;
        }

        $res = false;
        $this->loadAnswersList();
        $variants = $this->getAnswersList(self::ANSWERS_MODE_VARIANTS);
        $rights = $this->getAnswersList(self::ANSWERS_MODE_RIGHTS);
        unset($variants[$id]);
        unset($rights[$id]);

        if ($this->qTypeEntity->validateComplect($variants, $rights)) {
            $res = $this->qTypeEntity->delete($id);
            if ($res && $this->aid == $id) {
                $this->clearData(self::TYPE_ANSWER);
                return true;
            }
        }

        if ($this->qTypeEntity->hasErrors()) {
            $this->setErrors($this->qTypeEntity->getErrors());
        }

        return $res;
    }

    public function loadAnswer($id = 0)
    {
        if ($id > 0 && $id == $this->aid) {
            return $this->answerData;
        }

        $this->clearErrors();
        $this->clearData(self::TYPE_ANSWER);
        if (!$this->checkPermissions(self::CHECK_QID)) {
            return false;
        }

        $this->qTypeEntity->loadAnswer($id);
        if ($this->qTypeEntity->hasErrors()) {
            $this->setErrors($this->qTypeEntity->getErrors());
        } else {
            $this->answerData = $this->qTypeEntity->getAnswerData();
            $this->aid = $id;
        }

        return $this->answerData;
    }

    public function loadAnswersList()
    {
        if (!$this->checkPermissions(self::CHECK_QID)) {
            return false;
        }

        $this->qTypeEntity->loadAnswersList();
        $this->answersList = $this->qTypeEntity->getAnswersList();
        return true;
    }

    public function getQuestionsList()
    {
        return $this->questionsList;
    }

    public function getAnswersList($mode = self::ANSWERS_MODE_FULL)
    {
        if (!$this->checkPermissions(self::CHECK_QID)) {
            return false;
        }

        switch ($mode) {
            case self::ANSWERS_MODE_VARIANTS:
                $list = $this->qTypeEntity->getValidVariants();
                break;

            case self::ANSWERS_MODE_RIGHTS:
                $list = $this->qTypeEntity->getValidRights();
                break;

            case self::ANSWERS_MODE_FULL:
            default:
                $list = $this->answersList;
                break;

        }

        return $list;
    }

    public function getTestData()
    {
        return $this->testData;
    }

    public function getQuestionData()
    {
        return $this->questionData;
    }

    public function getTestId()
    {
        return $this->tid;
    }

    public function getQuestionId()
    {
        return $this->qid;
    }

    public function getAnswerId()
    {
        return $this->aid;
    }

    public function createQuestion($questionFields = [], $arVariants = [], $arRights = [])
    {
        $this->clearErrors();
        if (!$this->checkPermissions(self::CHECK_TID)) {
            return false;
        }

        $id = false;
        $questionFields['test_id'] = $this->tid;
        $questionFields['type'] = strtolower($questionFields['type']);
        $questionFields['ord'] = intval($questionFields['ord']);
        $questionFields = $this->checkFields($this->questionFieldsMap(), $questionFields, FieldsValidateTraitHelper::_ADD, $this->errors);

        if (!self::isQuestionTypeExists($questionFields['type'])) {
            $this->setErrors("Тип вопроса {$questionFields['type']} не найден");
        }

        if (!$this->hasErrors()) {
            DB::beginTransaction();

            $id = DB::table(TABLE_TEST_QUESTION)->insertGetId($questionFields);

            if ($this->loadQuestion($id) && $this->qTypeEntity) {
                if (!$this->qTypeEntity->validateComplect($arVariants, $arRights) || !$this->qTypeEntity->saveComplect()) {
                    $this->setErrors($this->qTypeEntity->getErrors());
                }
            };

            if ($this->hasErrors()) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        }

        return $id;
    }

    public function editQuestion($questionFields = [], $arVariants = [], $arRights = [], $id = 0)
    {
        $this->clearErrors();
        if (!$this->checkPermissions(self::CHECK_TID)) {
            return false;
        }

        $rows = false;
        $questionFields['ord'] = intval($questionFields['ord']);
        $questionFields = $this->checkFields($this->questionFieldsMap(), $questionFields, FieldsValidateTraitHelper::_EDIT, $this->errors);

        if (!$this->hasErrors()) {
            if ($this->loadQuestion($id) && $this->qTypeEntity) {
                if (!$this->qTypeEntity->validateComplect($arVariants, $arRights) || !$this->qTypeEntity->saveComplect()) {
                    $this->setErrors($this->qTypeEntity->getErrors());
                }
            };

            if (!$this->hasErrors()) {
                $rows = DB::table(TABLE_TEST_QUESTION)->where(['id' => $id, 'test_id' => $this->tid])->update($questionFields);
            }
        }

        return $rows;
    }

    public function createOrEditQuestion($questionFields = [], $arVariants = [], $arRights = [], $id = 0)
    {
        return $id
            ? $this->editQuestion($questionFields, $arVariants, $arRights, $id)
            : $this->createQuestion($questionFields, $arVariants, $arRights);
    }

    public function deleteQuestion($id = 0)
    {
        $this->clearErrors();
        if (!$this->checkPermissions(self::CHECK_TID)) {
            return false;
        }

        $res = DB::table(TABLE_TEST_QUESTION)->where(['id' => $id, 'test_id' => $this->tid])->first();

        if ($res && $this->qid == $id) {
            DB::table(TABLE_TEST_QUESTION)->delete($id);
            $this->clearData(self::TYPE_QUESTION);
            return true;
        }

        return false;
    }

    public function getBySubject($sid = 0)
    {
        return self::getList(TABLE_TEST, ['subject_id' => $sid, 'user_id' => $this->uid], 'title');
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
            self::CHECK_UID => 'Пользователь не загружен',
            self::CHECK_TID => 'Тест не загружен',
            self::CHECK_QID => 'Вопрос не загружен',
            self::CHECK_AID => 'Вариант ответа не загружен',
        ];

        $result = 0;
        foreach ($types as $type) {
            $check = false;
            switch ($type) {
                case self::CHECK_UID:
                    $check = !empty($this->uid);
                    break;

                case self::CHECK_TID:
                    $check = !empty($this->tid);
                    break;

                case self::CHECK_QID:
                    $check = !empty($this->qid) && is_object($this->qTypeEntity);
                    break;

                case self::CHECK_AID:
                    $check = !empty($this->aid);
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

    /**
     * Очищает группы загруженных данных
     * @param string $type
     */
    private function clearData($type = '')
    {
        $type = strtolower($type);
        if (empty($type)) {
            return;
        }

        // Порядок имеет значение!
        $types = [
            self::TYPE_ANSWER => false,
            self::TYPE_QUESTION => false,
            self::TYPE_TEST => false
        ];
        $types[$type] = true;

        foreach ($types as $key => $stop) {
            switch ($key) {
                case self::TYPE_ANSWER:
                    // @todo
                    break;

                case self::TYPE_QUESTION:
                    $this->qTypeEntity = null;
                    $this->qid = 0;
                    $this->questionData = [];
                    break;

                case self::TYPE_TEST:
                    $this->tid = 0;
                    $this->testData;
                    $this->questionsList = [];
                    break;

                default:
                    break;
            }
            if ($stop) {
                break;
            }
        }
    }

    // @todo ???
    public static function getList($table, $v = [], $orderColumn = 'id', $orderDirection = 'asc')
    {
        return DB::table($table)->where($v)->orderBy($orderColumn, $orderDirection)->get();
    }

    /**
     * Возвращает список всех доступных типов вопросов
     * @return array
     */
    public static function getQuestionTypes()
    {
        return Base::getConfig('question_types');
    }

    /**
     * Проверяет на существование переданный тип вопроса
     * @param string $type
     * @return bool
     */
    public static function isQuestionTypeExists($type = '')
    {
        return self::getQuestionTypeClass($type) !== false;
    }

    /**
     * Возвращает класс объекта указанного типа вопроса
     * @param string $type
     * @return bool|string
     */
    public static function getQuestionTypeClass($type = '')
    {
        if (!isset(Base::getConfig('question_types')[$type])) {
            return false;
        }

        $typeClassName = ucfirst(strtolower($type));
        $typeClass = '\\UTest\\Kernel\\Test\\Types\\' . $typeClassName;

        if (!class_exists($typeClass)) {
            return false;
        }

        return $typeClass;
    }
}
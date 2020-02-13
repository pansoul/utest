<?php

namespace UTest\Kernel\Test;

use UTest\Kernel\DB;
use UTest\Kernel\User\User;
use UTest\Kernel\Traits\FieldsValidateTraitHelper;
use UTest\Kernel\Base;

/**
 * Класс по созданию и управлению тестами и их вопросами.
 * Именно эти тесты являются тестами-основами.
 * @package UTest\Kernel\Test
 */
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

    private $uid = 0; // {userId} - Id пользователя (автор теста)
    private $tid = 0; // {testId} - Id теста-основы
    private $qid = 0; // {questionId} - Id вопроса
    private $aid = 0; // {answerId} - Id варианта ответа

    private $testData = [];
    private $questionData = [];
    private $answerData = [];
    private $questionsList = [];
    private $answersList = [];

    /**
     * @var \UTest\Kernel\Test\Types\AbstractType $qTypeEntity
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
            ]
        ];
    }

    /**
     * Создаёт новый тест
     * @param array $v
     * @return bool|int
     */
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

    /**
     * Редактирование теста
     *
     * @param array $v
     * @param int $id
     *
     * @return bool|int
     */
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

    /**
     * Передаёт управление на создание или редактирование теста на основе переданного параметра Id теста
     *
     * @param array $v
     * @param int $id
     *
     * @return bool|int
     */
    public function createOrEdit($v = [], $id = 0)
    {
        return $id ? $this->edit($v, $id) : $this->create($v);
    }

    /**
     * Удаление теста
     * @param int $id
     * @return bool|int
     */
    public function delete($id = 0)
    {
        $this->clearErrors();
        if (!$this->checkPermissions(self::CHECK_UID)) {
            return false;
        }

        $rows = false;
        if (DB::table(TABLE_TEST)->where(['id' => $id, 'user_id' => $this->uid])->exists()) {
            $rows = DB::table(TABLE_TEST)->delete($id);
        }

        if ($rows && $this->tid == $id) {
            $this->clearData(self::TYPE_TEST);
        }

        return $rows;
    }

    /**
     * Загружает данные теста и записывает их в свойства объекта
     * @param int $id
     * @return array|bool|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|null
     */
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

    /**
     * Загружает список вопросов теста и записывает их в свойства объекта
     * @return bool
     */
    public function loadQuestionsList()
    {
        $this->clearErrors();
        if (!$this->checkPermissions(self::CHECK_TID)) {
            return false;
        }

        $this->questionsList = DB::table(TABLE_TEST_QUESTION)->where('test_id', '=', $this->tid)->orderBy('ord', 'desc')->get();
        return true;
    }

    /**
     * Загружает вопрос и записывает его в свойства объекта
     * @param int $id
     * @return array|bool|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|null
     */
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

    /**
     * Удаляет вариант ответа вопроса
     * @param int $id
     * @return bool
     */
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
            }
        }

        if ($this->qTypeEntity->hasErrors()) {
            $this->setErrors($this->qTypeEntity->getErrors());
        }

        return $res;
    }

    /**
     * Загружает вариант ответа вопроса
     * @param int $id
     * @return array|bool
     */
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

    /**
     * Загружает список всех вариантов ответов вопроса и записывает их в свойство объекта
     * @return bool
     */
    public function loadAnswersList()
    {
        if (!$this->checkPermissions(self::CHECK_QID)) {
            return false;
        }

        $this->qTypeEntity->loadAnswersList();
        $this->answersList = $this->qTypeEntity->getAnswersList();
        return true;
    }

    /**
     * Возвращает список вопросов теста, полученный функцией loadQuestionsList()
     * @return array
     */
    public function getQuestionsList()
    {
        return $this->questionsList;
    }

    /**
     * Возвращает список вариантов ответов вопроса, полученный функцией loadAnswersList()
     * @param string $mode - какой набор данных вернуть [ANSWERS_MODE_VARIANTS|ANSWERS_MODE_RIGHTS|ANSWERS_MODE_FULL]
     * @return array|bool
     */
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

    /**
     * Возвращает данные теста, полученные функцией loadTest()
     * @return array
     */
    public function getTestData()
    {
        return $this->testData;
    }

    /**
     * Возвращает данные вопроса, полученные функцией loadQuestion()
     * @return array
     */
    public function getQuestionData()
    {
        return $this->questionData;
    }

    /**
     * Возвращает Id загруженного теста
     * @return int
     */
    public function getTestId()
    {
        return $this->tid;
    }

    /**
     * Возвращает Id загруженного вопроса
     * @return int
     */
    public function getQuestionId()
    {
        return $this->qid;
    }

    /**
     * Возвращает Id загруженного варианта ответа вопроса
     * @return int
     */
    public function getAnswerId()
    {
        return $this->aid;
    }

    /**
     * Создаёт полноценный вопрос с вариантами ответов
     *
     * @param array $questionFields
     * @param array $arVariants
     * @param array $arRights
     *
     * @return bool|int
     */
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

    /**
     * Редактирование вопроса с вариантами ответов
     *
     * @param array $questionFields
     * @param array $arVariants
     * @param array $arRights
     * @param int $id
     *
     * @return bool|int
     */
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
            DB::beginTransaction();

            if ($this->loadQuestion($id) && $this->qTypeEntity) {
                $this->loadAnswersList();
                $oldAnswerIds = array_reduce($this->getAnswersList(), function($acc, $item){
                    $acc[] = intval($item['id']);
                    return $acc;
                }, []);
                $newAnswerIds = array_reduce($arVariants, function($acc, $item){
                    $acc[] = intval($item['id']);
                    return $acc;
                }, []);
                $delAnswerIds = array_diff($oldAnswerIds, $newAnswerIds);

                $deleted = true;
                foreach ($delAnswerIds as $answerId) {
                    if (!$this->qTypeEntity->delete($answerId)) {
                        $deleted = false;
                        break;
                    }
                }

                if (!$deleted || !$this->qTypeEntity->validateComplect($arVariants, $arRights) || !$this->qTypeEntity->saveComplect()) {
                    $this->setErrors($this->qTypeEntity->getErrors());
                }
            };

            if ($this->hasErrors()) {
                DB::rollBack();
            } else {
                $rows = DB::table(TABLE_TEST_QUESTION)->where(['id' => $id, 'test_id' => $this->tid])->update($questionFields);
                DB::commit();
            }
        }

        return $rows;
    }

    /**
     * Передаёт управление на создание или редактирование вопроса на основе переданного параметра Id вопроса
     *
     * @param array $questionFields
     * @param array $arVariants
     * @param array $arRights
     * @param int $id
     *
     * @return bool|int
     */
    public function createOrEditQuestion($questionFields = [], $arVariants = [], $arRights = [], $id = 0)
    {
        return $id
            ? $this->editQuestion($questionFields, $arVariants, $arRights, $id)
            : $this->createQuestion($questionFields, $arVariants, $arRights);
    }

    /**
     * Удаляет вопрос
     * @param int $id
     * @return bool|int
     */
    public function deleteQuestion($id = 0)
    {
        $this->clearErrors();
        if (!$this->checkPermissions(self::CHECK_TID)) {
            return false;
        }

        $rows = false;
        if (DB::table(TABLE_TEST_QUESTION)->where(['id' => $id, 'test_id' => $this->tid])->exists()) {
            $rows = DB::table(TABLE_TEST_QUESTION)->delete($id);
        }

        if ($rows && $this->qid == $id) {
            $this->clearData(self::TYPE_QUESTION);
        }

        return $rows;
    }

    // @todo
    public function getBySubject($sid = 0)
    {
        return DB::table(TABLE_TEST)->where(['subject_id' => $sid, 'user_id' => $this->uid])->orderBy('title')->get();
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
                    $this->testData = [];
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

    /**
     * Возвращает список всех доступных типов вопросов
     * @return array
     */
    public static function getQuestionTypes()
    {
        return array_change_key_case(Base::getConfig('question_types'), CASE_LOWER);
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
        if (!isset(self::getQuestionTypes()[strtolower($type)])) {
            return false;
        }

        $typeClassName = ucfirst(strtolower($type));
        $typeClass = '\\UTest\\Kernel\\Test\\Question\\Types\\' . $typeClassName;

        if (!class_exists($typeClass)) {
            return false;
        }

        return $typeClass;
    }
}
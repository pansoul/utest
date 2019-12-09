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

    private $uid = 0;
    private $tid = 0;
    private $qid = 0;
    private $testData = [];
    private $subjectData = [];
    private $questionData = [];
    private $questionsList = [];

    public function __construct($uid, $tid = 0, $qid = 0)
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
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
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
                    FieldsValidateTraitHelper::_EDIT
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
            'test_id' => [
                FieldsValidateTraitHelper::_NAME => 'Привязка к тесту',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
            'ord' => [
                FieldsValidateTraitHelper::_NAME => 'Привязка к тесту',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
            'parent_id' => [
                FieldsValidateTraitHelper::_NAME => '???' // @todo
            ],
        ];
    }

    private function answerFieldsMap()
    {
        return [
            'title' => [
                FieldsValidateTraitHelper::_NAME => 'Название',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
            'question_id' => [
                FieldsValidateTraitHelper::_NAME => 'Привязка к вопросу',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
            'right_answer' => [
                FieldsValidateTraitHelper::_NAME => 'Верный ответ',
                FieldsValidateTraitHelper::_AVAILABLE => [
                    FieldsValidateTraitHelper::_ADD,
                    FieldsValidateTraitHelper::_EDIT
                ],
                FieldsValidateTraitHelper::_REQUIRED => true
            ],
        ];
    }

    public function create($v = [])
    {
        $this->clearErrors();

        $id = false;
        $v['user_id'] = $this->uid;
        $v = $this->checkFields($this->testFieldsMap(), $v, FieldsValidateTraitHelper::_ADD, $this->errors);

        if (!$this->hasErrors()) {
            $id = DB::table(TABLE_TEST)->insert($v);
        }

        return $id;
    }

    public function edit($v = [], $id = 0)
    {
        $this->clearErrors();

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
        return DB::table(TABLE_TEST)->where(['id' => $id, 'user_id' => $this->uid])->delete();
    }

    public function loadTest($id = 0)
    {
        $this->clearErrors();
        $res = DB::table(TABLE_TEST)->where(['id' => $id, 'user_id' => $this->uid])->first();
        if (!$res) {
            $this->setErrors('Тест не найден');
        } else {
            $this->testData = $res;
            $this->tid = $id;
        }
        return $res;
    }

    public function loadQuestionsList()
    {
        $this->questionsList = DB::table(TABLE_TEST_QUESTION)->where('test_id', '=', $this->tid)->orderBy('ord')->get();
    }

    public function loadQuestion($id = 0)
    {
        $this->clearErrors();
        $res = DB::table(TABLE_TEST_QUESTION)->where(['id' => $id, 'test_id' => $this->tid])->first();
        if (!$res) {
            $this->setErrors('Вопрос не найден');
        } else {
            $this->questionData = $res;
            $this->qid = $id;
        }
        return $res;
    }

    public function getQuestionsList()
    {
        return $this->questionsList;
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

    public function createQuestion()
    {

    }

    public function editQuestion()
    {

    }

    public function createOrEditQuestion()
    {

    }

    public function deleteQuestion()
    {

    }

    public function getBySubject($sid = 0)
    {
        return self::getList(TABLE_TEST, ['subject_id' => $sid, 'user_id' => $this->uid], 'title');
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
}
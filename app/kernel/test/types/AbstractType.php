<?php

namespace UTest\Kernel\Test\Types;

use UTest\Kernel\DB;
use UTest\Kernel\Traits\FieldsValidateTraitHelper;

abstract class AbstractType implements TypeInterface
{
    use \UTest\Kernel\Traits\ErrorsManageTrait;
    use \UTest\Kernel\Traits\FieldsValidateTrait;

    protected $qid = 0; // Id вопроса
    protected $id = 0; // Id варианта ответа
    protected $validVariants = null; // Корректный список вариантов ответов
    protected $validRights = null; // Корректный список верных ответов
    protected $answerData = []; // Данные варианта ответа
    protected $answersList = []; // Список всех вариантов ответа

    public function __construct($qid = 0, $id = 0)
    {
        if ($this->checkQuestionExists($qid)) {
            $this->qid = $qid;
        }

        if ($id) {
            $this->loadAnswer($id);
        }
    }

    protected function answerFieldsMap()
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
                    FieldsValidateTraitHelper::_ADD
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

    abstract protected function filterRights($r = null);

    protected function filterVariants($v = [])
    {
        $v = array_map(function($item){
            $item['title'] = trim($item['title']);
            $item['id'] = trim(strval($item['id']));
            return $item;
        }, $v);

        return array_filter($v, function($item){
            return !empty($item['title']);
        });
    }

    public function loadAnswersList()
    {
        if (!$this->qid) {
            $this->setErrors('Вопрос не загружен');
            return;
        }
        $this->answersList = DB::table(TABLE_TEST_ANSWER)->where('question_id', '=', $this->qid)->orderBy('id')->get()->toArray();
        $this->validVariants = array_reduce($this->answersList, function($acc, $item){
            $acc[$item['id']] = [
                'id' => $item['id'],
                'title' => $item['title']
            ];
            return $acc;
        }, []);
        $this->validRights = $this->filterRights(array_reduce($this->answersList, function($acc, $item){
            $acc[$item['id']] = $item['right_answer'];
            return $acc;
        }, []));
    }

    public function loadAnswer($id = 0)
    {
        if (!$this->qid) {
            $this->setErrors('Вопрос не загружен');
            return [];
        }
        $res = DB::table(TABLE_TEST_ANSWER)->where(['question_id' => $this->qid, 'id' => $id])->first();
        if (!$res) {
            $this->setErrors('Вариант ответа не найден');
        } else {
            $this->answerData = $res;
            $this->id = $id;
            $this->validVariants = $res['title'];
            $this->validRights = $res['right_answer'];
        }
        return $res;
    }

    public function create($v = [], $loadCreatedAnswer = false)
    {
        $this->clearErrors();

        $id = false;
        $v['question_id'] = $this->qid;
        $v = $this->checkFields($this->answerFieldsMap(), $v, FieldsValidateTraitHelper::_ADD, $this->errors);

        if (!$this->hasErrors()) {
            $id = DB::table(TABLE_TEST_ANSWER)->insertGetId($v);
            if ($loadCreatedAnswer) {
                $this->loadAnswer($id);
            }
        }

        return $id;
    }

    public function edit($v = [], $id = 0)
    {
        $this->clearErrors();

        $rows = false;
        $v = $this->checkFields($this->answerFieldsMap(), $v, FieldsValidateTraitHelper::_EDIT, $this->errors);

        if (!$this->hasErrors()) {
            $rows = DB::table(TABLE_TEST_ANSWER)->where(['id' => $id, 'question_id' => $this->qid])->update($v);
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

        if ($this->checkAnswerExists($id)) {
            DB::table(TABLE_TEST_ANSWER)->delete($id);
            return true;
        }

        return false;
    }

    public function getValidVariants()
    {
        return $this->validVariants;
    }

    public function getValidRights()
    {
        return $this->validRights;
    }

    public function getAnswersList()
    {
        return $this->answersList;
    }

    public function getAnswerData()
    {
        return $this->answerData;
    }

    public function checkQuestionExists($id = null)
    {
        $id = is_null($id) ? $this->qid : intval($id);
        $res = DB::table(TABLE_TEST_QUESTION)->where('id', '=', $id)->exists();

        if (!$res) {
            $this->setErrors('Id вопроса не найден или указан неверно');
        }

        return $res;
    }

    public function checkAnswerExists($id = null)
    {
        $id = is_null($id) ? $this->id : intval($id);
        $res = DB::table(TABLE_TEST_ANSWER)->where(['id' => $id, 'question_id' => $this->qid])->exists();

        if (!$res) {
            $this->setErrors('Id варианта ответа не найден или указан неверно');
        }

        return $res;
    }

    public function checkVariantsCompleted()
    {
        if (is_null($this->validVariants) || is_null($this->validRights)) {
            $this->setErrors('Не указаны варианты ответов и/или верные ответы');
            return false;
        }

        return true;
    }
}

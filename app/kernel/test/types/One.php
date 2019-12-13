<?php

namespace UTest\Kernel\Test\Types;

class One extends AbstractType
{
    protected function filterRights($r = null)
    {
        if (is_array($r)) {
            return key(array_filter($r, function($value){
                return $value == 1;
            }));
        } else {
            return trim(strval($r));
        }
    }

    public function validateComplect($v = [], $r = '')
    {
        $this->clearErrors();

        $v = $this->filterVariants($v);
        $r = $this->filterRights($r);

        if (!count($v)) {
            $this->setErrors('Необходимо заполнить варианты ответов');
        } elseif (count($v) == 1) {
            $this->setErrors('Мин. количество вариантов должно быть не меньше 2');
        }

        if (!isset($v[$r])) {
            $this->setErrors('Не указан верный ответ');
        }

        if ($this->hasErrors()) {
            return false;
        }

        $this->validVariants = $v;
        $this->validRights = $r;

        return true;
    }

    public function saveComplect()
    {
        $this->clearErrors();

        if (!$this->checkQuestionExists() || !$this->checkVariantsCompleted()) {
            return false;
        }

        foreach ($this->validVariants as $k => $item) {
            $dataRow = [
                'title' => $item['title'],
                'question_id' => $this->qid,
                'right_answer' => intval($k == $this->validRights)
            ];

            $this->createOrEdit($dataRow, $item['id']);

            if ($this->hasErrors()) {
                return false;
            }
        }

        return true;
    }
}
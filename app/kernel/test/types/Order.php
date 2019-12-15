<?php

namespace UTest\Kernel\Test\Types;

class Order extends AbstractType
{
    protected function filterRights($r = null)
    {
        return array_unique($r);
    }

    public function validateComplect($v = [], $r = [])
    {
        $this->clearErrors();

        $v = $this->filterVariants($v);
        $r = $this->filterRights($r);

        // @todo разобратсья с порядком вывода ошибок
        if (!count($v)) {
            $this->setErrors('Необходимо заполнить варианты ответов');
        } elseif (count($v) == 1) {
            $this->setErrors('Мин. количество заполненных вариантов должно быть не меньше 2');
        } elseif (max($r) != count($v)) {
            $this->setErrors('Верные позиции должны быть уникальны и не могут повторяться');
        } elseif (count($r) != count($v)) {
            $this->setErrors('Заполните все данные вариантов ответов');
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
                'right_answer' => $this->validRights[$k]
            ];

            $this->createOrEdit($dataRow, $item['id']);

            if ($this->hasErrors()) {
                return false;
            }
        }

        return true;
    }
}
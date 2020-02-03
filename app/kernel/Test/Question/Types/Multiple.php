<?php

namespace UTest\Kernel\Test\Question\Types;

use UTest\Kernel\Test\Question\AbstractType;

class Multiple extends AbstractType
{
    protected function filterRights($r = [])
    {
        return array_filter($r, function($value){
            return $value == 1;
        });
    }

    public function validateComplect($v = [], $r = [])
    {
        $this->clearErrors();

        $v = $this->filterVariants($v);
        $r = $this->filterRights($r);

        if (!count($v)) {
            $this->setErrors('Необходимо заполнить варианты ответов');
        } elseif (count($v) == 1) {
            $this->setErrors('Мин. количество заполненных вариантов должно быть не меньше 2');
        }

        if (!count($r)) {
            $this->setErrors('Не указаны верные ответы');
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
                'right_answer' => intval(isset($this->validRights[$k]))
            ];

            $this->createOrEdit($dataRow, $item['id']);

            if ($this->hasErrors()) {
                return false;
            }
        }

        return true;
    }

    protected function checkAnswer($userAnswer = null)
    {
        $userAnswer = ksort($this->filterRights($userAnswer));
        $validRights = ksort($this->getValidRights());
        return $validRights === $userAnswer;
    }
}
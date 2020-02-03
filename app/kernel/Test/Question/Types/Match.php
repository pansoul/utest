<?php

namespace UTest\Kernel\Test\Question\Types;

use UTest\Kernel\Test\Question\AbstractType;

class Match extends AbstractType
{
    protected function filterRights($r = null)
    {
        if (is_array($r)) {
            return array_shift($r);
        } else {
            return trim(strval($r));
        }
    }

    public function validateComplect($v = [], $r = '')
    {
        $this->clearErrors();

        $r = $this->filterRights($r);
        $v['title'] = $r;

        if (empty($r)) {
            $this->setErrors('Не указано точное написание верного ответа');
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

        $dataRow = [
            'title' => $this->validRights,
            'question_id' => $this->qid,
            'right_answer' => $this->validRights
        ];

        $this->createOrEdit($dataRow, $this->validVariants['id']);

        if ($this->hasErrors()) {
            return false;
        }

        return true;
    }

    protected function checkAnswer($userAnswer = null)
    {
        $userAnswer = $this->filterRights($userAnswer);
        $validRights = $this->getValidRights();
        return strcasecmp($validRights, $userAnswer) == 0;
    }
}
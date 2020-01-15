<?php

namespace UTest\Components;

use UTest\Kernel\DB;
use UTest\Kernel\Test\Result;
use UTest\Kernel\User\User;
use UTest\Kernel\Test\Passage;
use UTest\Kernel\Component\Controller;

class StudentTestsModel extends \UTest\Kernel\Component\Model
{
    public function subjectsAction()
    {
        $res = DB::table(TABLE_STUDENT_TEST)
            ->select(
                TABLE_STUDENT_TEST.'.id',
                TABLE_PREPOD_SUBJECT.'.title as subject_name',
                TABLE_PREPOD_SUBJECT.'.alias as subject_code',
                TABLE_PREPOD_SUBJECT.'.id as subject_id',
                TABLE_USER.'.name as prepod_name',
                TABLE_USER.'.last_name as prepod_last_name',
                TABLE_USER.'.surname as prepod_surname',
                DB::raw('count('.TABLE_STUDENT_TEST.'.id) as test_count')
            )
            ->leftJoin(TABLE_PREPOD_SUBJECT, TABLE_PREPOD_SUBJECT.'.id', '=', TABLE_STUDENT_TEST.'.subject_id')
            ->leftJoin(TABLE_USER, TABLE_USER.'.id', '=', TABLE_PREPOD_SUBJECT.'.user_id')
            ->where(TABLE_STUDENT_TEST.'.group_id', '=', User::user()->getGroupId())
            ->groupBy(TABLE_STUDENT_TEST.'.subject_id')
            ->orderBy('subject_name')
            ->get()
            ->toArray();

        foreach ($res as $k => $item) {
            $arTestCountByStatus = DB::table(TABLE_STUDENT_TEST)
                ->select(
                    TABLE_STUDENT_TEST_PASSAGE.'.status',
                    DB::raw('count('.TABLE_STUDENT_TEST_PASSAGE.'.status) as count')
                )
                ->leftJoin(TABLE_STUDENT_TEST_PASSAGE, function($join){
                    $join->on(TABLE_STUDENT_TEST_PASSAGE.'.test_id', '=', TABLE_STUDENT_TEST.'.id')
                        ->where(TABLE_STUDENT_TEST_PASSAGE.'.user_id', '=', User::user()->getUID());
                })
                ->where(TABLE_STUDENT_TEST.'.group_id', '=', User::user()->getGroupId())
                ->where(TABLE_STUDENT_TEST.'.subject_id', '=', $item['subject_id'])
                ->groupBy(TABLE_STUDENT_TEST_PASSAGE.'.status')
                ->get()
                ->toArray();

            $arTestCountByStatus = array_reduce($arTestCountByStatus, function($acc, $el){
                $acc[intval($el['status'])] = $el['count'];
                return $acc;
            }, []);
            if ($item['test_count'] != array_sum($arTestCountByStatus)) {
                $arTestCountByStatus[Passage::STATUS_WAITED_FOR_START] = $item['test_count'] - array_sum($arTestCountByStatus);
            }

            $res[$k]['status_count'] = $arTestCountByStatus;
        }

        $this->setData($res);
    }

    public function testListAction($subjectCode)
    {
        $subject = DB::table(TABLE_STUDENT_TEST)
            ->select(TABLE_PREPOD_SUBJECT.'.id')
            ->leftJoin(TABLE_PREPOD_SUBJECT, TABLE_PREPOD_SUBJECT.'.id', '=', TABLE_STUDENT_TEST.'.subject_id')
            ->where(TABLE_PREPOD_SUBJECT.'.alias', '=', $subjectCode)
            ->where(TABLE_STUDENT_TEST.'.group_id', '=', User::user()->getGroupId())
            ->first();

        if (!$subject) {
            $this->setErrors('Предмет не найден', ERROR_ELEMENT_NOT_FOUND);
        } else {
            $res = DB::table(TABLE_STUDENT_TEST)
                ->select(
                    TABLE_STUDENT_TEST.'.*',
                    TABLE_STUDENT_TEST_PASSAGE.'.status',
                    TABLE_STUDENT_TEST_PASSAGE.'.retake'
                )
                ->leftJoin(TABLE_STUDENT_TEST_PASSAGE, function($join){
                    $join->on(TABLE_STUDENT_TEST_PASSAGE.'.test_id', '=', TABLE_STUDENT_TEST.'.id')
                        ->where(TABLE_STUDENT_TEST_PASSAGE.'.user_id', '=', User::user()->getUID());
                })
                ->where(TABLE_STUDENT_TEST.'.group_id', '=', User::user()->getGroupId())
                ->where(TABLE_STUDENT_TEST.'.subject_id', '=', $subject['id'])
                ->orderBy(TABLE_STUDENT_TEST.'.title')
                ->get();
        }

        $this->setData($res);
        return $res;
    }
    
    public function runAction($subjectCode, $id)
    {
        $this->testListAction($subjectCode);
        if ($this->hasErrors(ERROR_ELEMENT_NOT_FOUND)) {
            $this->setData(null);
            return;
        }

        $passage = new Passage(User::user()->getUID(), $id);

        if ($passage->hasErrors()) {
            $this->setErrors($passage->getErrors(), ERROR_ELEMENT_NOT_FOUND);
        } elseif ($passage->isFinished()) {
            $this->setErrors('Тест завершён', ERROR_ELEMENT_NOT_FOUND);
        }

        $this->setData([
            'questions_count' => $passage->getNumberQuestions(),
            'question' => $passage->resume(),
            'options' => $passage->getOptions(),
            'used' => $passage->getUsedQuestionsIds()
        ]);
    }

    public function ajaxStartAction($id)
    {
        $result = [];

        $passage = new Passage(User::user()->getUID(), $id);
        $passage->start();

        if ($q = $passage->loadQuestion(1)) {
            $result['status'] = 'OK';
            $result['question'] = $this->buildQuestionDataForAjax($q);
        } else {
            $result['status'] = 'ERROR';
            $result['message'] = $passage->getErrors();
        }

        $this->setData($result);
    }

    public function ajaxGotoAction($id, $number)
    {
        $result = [];

        $passage = new Passage(User::user()->getUID(), $id);
        $this->saveUserAnswerAction($passage);

        if (!$this->hasErrors()) {
            $q = $passage->loadQuestion($number == 'next' ? $passage->getNextQuestionNumber() : $number);
        }

        if ($q) {
            $result['status'] = 'OK';
            $result['question'] = $this->buildQuestionDataForAjax($q);
        } else {
            $result['status'] = 'ERROR';
            $result['message'] = $passage->getErrors();
        }

        $this->setData($result);
    }

    public function ajaxFinishAction($id)
    {
        $result = [];

        $passage = new Passage(User::user()->getUID(), $id);
        $this->saveUserAnswerAction($passage);

        if (!$this->hasErrors()) {
            $testResult = new Result(User::user()->getUID(), $id);
            $testResultData = $testResult->getResult();
        }

        if (!$this->hasErrors() && $passage->finish()) {
            $result['status'] = 'OK';
            $result['result'] = Controller::loadComponent('utility', 'testresult', [$testResultData]);
        } else {
            $result['status'] = 'ERROR';
            $result['message'] = $passage->getErrors();
        }

        $this->setData($result);
    }

    private function saveUserAnswerAction(Passage $passage)
    {
        $this->clearErrors();
        $v = $this->_POST;
        $passage->saveAnswer($v['right'], $passage->getLastNumberQuestion());
        if ($passage->hasErrors()) {
            $this->setErrors($passage->getErrors());
        }
    }

    private function buildQuestionDataForAjax($q)
    {
        return [
            'text' => html_entity_decode($q['question']['text']),
            'cur_num' => $q['cur_num'],
            'variants' => $this->includeTemplate('answer_' . $q['question']['type'], $q)
        ];
    }
}
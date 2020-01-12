<?php

namespace UTest\Components;

use UTest\Kernel\DB;
use UTest\Kernel\User\User;
use UTest\Kernel\Test\Passage;

class StudentTestsModel extends \UTest\Kernel\Component\Model
{
    public function subjectsAction()
    {
        $res = DB::table(TABLE_STUDENT_TEST)
            ->select(
                TABLE_STUDENT_TEST.'.id',
                TABLE_PREPOD_SUBJECT.'.title as subject_name',
                TABLE_PREPOD_SUBJECT.'.alias as subject_code',
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
            ->get();

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
        return $subject;
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
            $result['question'] = [
                'text' => html_entity_decode($q['question']['text']),
                'cur_num' => $q['cur_num'],
                'variants' => '' // @todo
            ];
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
        $q = $passage->loadQuestion($number == 'next' ? $passage->getNextQuestionNumber() : $number);

        if ($q) {
            $result['status'] = 'OK';
            $result['question'] = [
                'text' => html_entity_decode($q['question']['text']),
                'cur_num' => $q['cur_num'],
                'variants' => '' // @todo
            ];
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

        if ($passage->finish()) {
            $result['status'] = 'OK';
            $result['result'] = ''; // @todo
        } else {
            $result['status'] = 'ERROR';
            $result['message'] = $passage->getErrors();
        }

        $this->setData($result);
    }
    
    /*public function endAction()
    {
        $res = array();
        
        if (!$this->request->isAjaxRequest())
            USite::redirect(USite::getModurl());
        
        $test = new Test($_SESSION['stid'], UUser::user()->getUID());   
        
        if (!empty(Test::$last_errors)) {
            $res['status'] = 'ERROR';
            $res['status_message'] = Test::$last_errors;
            header('Content-Type: application/json');
            return json_encode($res);            
        }
        
        $r = $test->endTest();
        
        if (!$r) {
            $res['status'] = 'ERROR';
            $res['status_message'] = Test::$last_errors;
            header('Content-Type: application/json');
            return json_encode($res);  
        }        
        
        $testResult = new UTResult($_SESSION['stid']);     
        $tprop = $testResult->getTProp();
        $res['status'] = 'OK';
        $res['result'] = USiteController::loadComponent('utility', 'testresult', array($testResult->getResult(false), $tprop['retake']));
        header('Content-Type: application/json');
        return json_encode($res);     
    }*/

}
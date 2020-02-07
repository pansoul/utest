<?php

class Prepod extends UUser {
    
    public function __construct()
    {
        //
    }
    
    public function createTest($arFields)
    {
        $result = Test::createOrEdit($arFields, self::$uid);
        self::$last_errors = Test::$last_errors;
        return $result;
    }
    
    public function editTest($arFields)
    {
        $result = Test::createOrEdit($arFields, self::$uid);
        self::$last_errors = Test::$last_errors;
        return $result;
    }
    
    public function deleteTest($tid)
    {
        return Test::delete($tid, self::$uid);        
    }
    
    public function getTestList($sid)
    {
        return Test::getTestsBySubject($sid);
    }    
    
    public function getQuestionList($tid)
    {
        $test = new Test($tid, self::$uid, true);
        
        if (!empty(Test::$last_errors)) {
            self::$last_errors = Test::$last_errors;
            return false;
        }
        
        return $test->getQuestionList();
    }
    
    public function createQuestion($tid, $arQuestion, &$arAnswer, $arRight)
    {
        $test = new Test($tid, self::$uid, true);
        $qResult = $test->createQuestion($arQuestion);
        self::$last_errors = Test::$last_errors;
        $aResult = $test->saveAnswerList($arAnswer, $arRight);
        $arAnswer = $test->getAnswerList();        
        self::$last_errors = array_merge(self::$last_errors, Test::$last_errors);

        if ($qResult && $aResult) {
            $test->runStackExecuting();
            return true;
        }
        
        return false;
    }
    
    public function _prepareEditQuestion($tid, $qid)
    {
        $v = array();
        $test = new Test($tid, UUser::user()->getUID(), true);
        $test->loadFullQuestion($qid);
        $v['question'] = $test->getAdvTextQuestion();  
        $v['answer'] = $test->getAnswerList();    
        foreach ($v['answer'] as $item)
        {
            if ($item['right_answer'])
                $v['right_answer'] = $item['right_answer'];
        }
        
        return $v;
    }
    
    public function editQuestion($tid, $qid, $arQuestion, &$arAnswer, $arRight)
    {
        $test = new Test($tid, self::$uid, true);
        $test->loadFullQuestion($qid);
        $qResult = $test->editQuestion($arQuestion);
        self::$last_errors = Test::$last_errors;
        $aResult = $test->saveAnswerList($arAnswer, $arRight);
        $arAnswer = $test->getAnswerList();
        self::$last_errors = array_merge(self::$last_errors, Test::$last_errors);

        if ($qResult && $aResult) {
            $test->runStackExecuting();
            return true;
        }
        
        return false;
    }
    
    public function deleteQuestion($tid, $qid)
    {
        $test = new Test($tid, self::$uid, true);
        $test->loadFullQuestion($qid);
        
        if (!empty(Test::$last_errors)) {
            self::$last_errors = Test::$last_errors;
            return false;
        }
        
        $test->deleteQuestion();
        return true;
    }
    
}

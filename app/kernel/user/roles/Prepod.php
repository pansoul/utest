<?php

namespace UTest\Kernel\User\Roles;

use UTest\Kernel\DB;

class Prepod extends \UTest\Kernel\User\User
{
    const ROLE = 'prepod';

    public function createTest($arFields)
    {
        $test = new UTest();
        $result = $test->create($arFields, self::$uid);
        self::$last_errors = $test->last_errors;
        return $result;
    }
//
//    public function editTest($tid, $arFields)
//    {
//        $test = new UTest($tid, self::$uid);
//        $result = $test->edit($arFields);
//        self::$last_errors = $test->last_errors;
//        return $result;
//    }
//
//    public function deleteTest($tid)
//    {
//        $test = new UTest($tid, self::$uid);
//        return $test->delete();
//    }
//
//    public function getTestList($sid)
//    {
//        return UTest::getList(self::$uid, $sid);
//    }
//
//    public function getQuestionList($tid)
//    {
//        $test = new UTest($tid, self::$uid);
//        $result = $test->getQuestionList();
//        self::$last_errors = $test->last_errors;
//        return $result;
//    }
//
//    public function createQuestion($tid, $arQuestion, &$arAnswer, $arRight)
//    {
//        $test = new UTest($tid, self::$uid);
//        $result = $test->createFullQuestion($arQuestion, $arAnswer, $arRight);
//        self::$last_errors = $test->last_errors;
//        $arAnswer = $test->getAnswerList(true);
//        return $result;
//    }
//
//    public function _prepareEditQuestion($tid, $qid)
//    {
//        $test = new UTest($tid, self::$uid);
//        return $test->loadFullQuestion($qid);
//    }
//
//    public function editQuestion($tid, $qid, $arQuestion, &$arAnswer, $arRight)
//    {
//        $test = new UTest($tid, self::$uid);
//        $result = $test->loadQuestion($qid)->editFullQuestion($arQuestion, $arAnswer, $arRight);
//        self::$last_errors = $test->last_errors;
//        $arAnswer = $test->getAnswerList(true);
//        return $result;
//    }
//
//    public function deleteQuestion($tid, $qid)
//    {
//        $test = new UTest($tid, self::$uid);
//        $result = $test->deleteQuestion($qid);
//        self::$last_errors = $test->last_errors;
//        return $result;
//    }
//
//    public function deleteAnswer($tid, $qid, $aid)
//    {
//        $test = new UTest($tid, self::$uid);
//        $test->loadQuestion($qid);
//        $result = $test->deleteAnswer($aid);
//        self::$last_errors = $test->last_errors;
//        return $result;
//    }
}

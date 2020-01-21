<?php

namespace UTest\Components;

use UTest\Kernel\DB;
use UTest\Kernel\User\User;
use UTest\Kernel\Test\Passage;
use UTest\Kernel\Test\Result;
use UTest\Kernel\Component\Controller;

class StudentResultsModel extends \UTest\Kernel\Component\Model
{
    public function testListAction()
    {
        $res = DB::table(TABLE_STUDENT_TEST_PASSAGE)
            ->select(
                TABLE_STUDENT_TEST_PASSAGE.'.id',
                TABLE_STUDENT_TEST_PASSAGE.'.retake',
                TABLE_STUDENT_TEST.'.title as test_title',
                TABLE_STUDENT_TEST.'.id as test_id',
                TABLE_PREPOD_SUBJECT.'.title as subject',
                TABLE_STUDENT_TEST_TIME.'.date_finish'
            )
            ->leftJoin(TABLE_STUDENT_TEST, TABLE_STUDENT_TEST.'.id', '=', TABLE_STUDENT_TEST_PASSAGE.'.test_id')
            ->leftJoin(TABLE_PREPOD_SUBJECT, TABLE_PREPOD_SUBJECT.'.id', '=', TABLE_STUDENT_TEST.'.subject_id')
            ->leftJoin(TABLE_STUDENT_TEST_TIME, function($join){
                $join->on(TABLE_STUDENT_TEST_TIME.'.test_id', '=', TABLE_STUDENT_TEST_PASSAGE.'.test_id')
                    ->where(TABLE_STUDENT_TEST_TIME.'.retake_value', '=', TABLE_STUDENT_TEST_PASSAGE.'.retake');
            })
            ->where([
                TABLE_STUDENT_TEST_PASSAGE.'.user_id' => User::user()->getUID(),
                TABLE_STUDENT_TEST_PASSAGE.'.status' => Passage::STATUS_FINISHED
            ])
            ->orderBy(TABLE_STUDENT_TEST_TIME.'.date_finish')
            ->groupBy(TABLE_STUDENT_TEST_PASSAGE.'.id')
            ->get()
            ->toArray();

        $this->setData($res);
    }

    public function resultAction($id)
    {
        Controller::includeComponentFiles('utility');
        $result = new Result(User::user()->getUID(), $id);
        $resultTemplate = Controller::loadComponent('utility', 'test_result', [
            'result' => $result,
            'mode' => UtilityModel::RESULT_MODE_DETAIL
        ]);
        $this->setData($resultTemplate);
        $this->setErrors($result->getErrors());
    }
}
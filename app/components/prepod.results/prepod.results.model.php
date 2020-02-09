<?php

namespace UTest\Components;

use UTest\Kernel\DB;
use UTest\Kernel\User\User;
use UTest\Kernel\Test\Assignment;
use UTest\Kernel\Test\Result;
use UTest\Kernel\Component\Controller;
use UTest\Kernel\Site;

class PrepodResultsModel extends \UTest\Kernel\Component\Model
{
    private $table_subject = 'u_prepod_subject';
    private $table_test = 'u_test';
    private $table_group = 'u_univer_group';
    private $table_student_test = 'u_student_test';
    private $table_user = 'u_user';
    private $table_student_passage = 'u_student_test_passage';
    private $table_student_time = 'u_student_test_time';

    public function groupsAction()
    {
        $res = DB::table(TABLE_UNIVER_GROUP)
            ->select(
                TABLE_UNIVER_GROUP.'.*',
                TABLE_UNIVER_SPECIALITY.'.title as speciality_title',
                TABLE_UNIVER_FACULTY.'.title as faculty_title'
            )
            ->leftJoin(TABLE_UNIVER_SPECIALITY, TABLE_UNIVER_SPECIALITY.'.id', '=', TABLE_UNIVER_GROUP.'.speciality_id')
            ->leftJoin(TABLE_UNIVER_FACULTY, TABLE_UNIVER_FACULTY.'.id', '=', TABLE_UNIVER_SPECIALITY.'.faculty_id')
            ->orderBy(TABLE_UNIVER_GROUP.'.title')
            ->get();

        $this->setData($res);
    }

    public function subjectsAction($groupCode)
    {
        $group = DB::table(TABLE_UNIVER_GROUP)->where('alias', '=', $groupCode)->first();

        $res = DB::table(TABLE_PREPOD_SUBJECT)
            ->select(
                TABLE_PREPOD_SUBJECT.'.*',
                DB::raw('count('.TABLE_STUDENT_TEST.'.id) as test_count')
            )
            ->leftJoin(TABLE_STUDENT_TEST, function($join) use ($group) {
                $join->on(TABLE_STUDENT_TEST.'.subject_id', '=', TABLE_PREPOD_SUBJECT.'.id')
                    ->where(TABLE_STUDENT_TEST.'.group_id', '=', $group['id'])
                    ->where(TABLE_STUDENT_TEST.'.user_id', '=', User::user()->getUID());
            })
            ->where(TABLE_PREPOD_SUBJECT.'.user_id', '=', User::user()->getUID())
            ->groupBy(TABLE_PREPOD_SUBJECT.'.id')
            ->orderBy(TABLE_PREPOD_SUBJECT.'.title')
            ->get();

        if (!$group) {
            $this->setErrors('Группа не найдена', ERROR_ELEMENT_NOT_FOUND);
        }

        $this->setData($res);
        return $group;
    }

    public function testsAction($groupCode, $subjectCode)
    {
        $group = $this->subjectsAction($groupCode);
        if ($this->hasErrors(ERROR_ELEMENT_NOT_FOUND)) {
            $this->setData(null);
            return;
        }

        $subject = DB::table(TABLE_PREPOD_SUBJECT)
            ->where('alias', '=', $subjectCode)
            ->where('user_id', '=', User::user()->getUID())
            ->first();

        $res = DB::table(TABLE_STUDENT_TEST)
            ->select(
                TABLE_STUDENT_TEST.'.*',
                TABLE_TEST.'.title as base_title'
            )
            ->leftJoin(TABLE_TEST, TABLE_TEST.'.id', '=', TABLE_STUDENT_TEST.'.test_id')
            ->where([
                TABLE_STUDENT_TEST.'.group_id' => $group['id'],
                TABLE_STUDENT_TEST.'.subject_id' => $subject['id'],
                TABLE_STUDENT_TEST.'.user_id' => User::user()->getUID(),
            ])
            ->orderBy(TABLE_STUDENT_TEST.'.date')
            ->get();

        if (!$subject) {
            $this->setErrors('Предмет не найден', ERROR_ELEMENT_NOT_FOUND);
        }

        $this->setData($res);

        return [
            'group' => $group,
            'subject' => $subject
        ];
    }

    public function studentsAction($groupCode, $subjectCode, $atid)
    {
        $data = $this->testsAction($groupCode, $subjectCode);
        if ($this->hasErrors(ERROR_ELEMENT_NOT_FOUND)) {
            $this->setData(null);
            return;
        }

        $group = $data['group'];
        $subject = $data['subject'];
        $assignedTest = new Assignment(User::user()->getUID(), $atid);

        if ($assignedTest->hasErrors()) {
            $this->setErrors($assignedTest->getErrors(), ERROR_ELEMENT_NOT_FOUND);
            $this->setData(null);
            return;
        }

        $res = DB::table(TABLE_USER)
            ->select(
                TABLE_USER.'.id',
                TABLE_USER.'.last_name',
                TABLE_USER.'.name',
                TABLE_USER.'.surname',
                TABLE_STUDENT_TEST_PASSAGE.'.id as atid',
                TABLE_STUDENT_TEST_PASSAGE.'.retake as retake_value',
                TABLE_STUDENT_TEST_PASSAGE.'.status as test_status'
            )
            ->leftJoin(TABLE_STUDENT_TEST_PASSAGE, function($join) use ($assignedTest) {
                $join->on(TABLE_STUDENT_TEST_PASSAGE.'.user_id', '=', TABLE_USER.'.id')
                    ->where(TABLE_STUDENT_TEST_PASSAGE.'.test_id', '=', $assignedTest->getAssignedTestId());
            })
            ->where(TABLE_USER.'.group_id', '=', $group['id'])
            ->orderBy(TABLE_USER.'.last_name')
            ->get();

        $this->setData($res);
    }

    public function resultAction($atid, $uid)
    {
        $assignedTest = new Assignment(User::user()->getUID(), $atid);

        if ($assignedTest->hasErrors()) {
            $this->setErrors($assignedTest->getErrors());
            return;
        }

        Controller::includeComponentFiles('utility');
        $result = new Result($uid, $atid, $this->_GET['retake']);
        $this->setErrors($result->getErrors());
        $resultTemplate = Controller::loadComponent('utility', 'test_result', [
            'result' => $result,
            'mode' => UtilityModel::RESULT_MODE_FULL
        ]);
        $this->setData($resultTemplate);
    }
    
    public function retakeStudentAction($atid, $uid)
    {
        $assignedTest = new Assignment(User::user()->getUID(), $atid);

        if ($assignedTest->hasErrors()) {
            $this->setErrors($assignedTest->getErrors(), ERROR_ELEMENT_NOT_FOUND);
            return;
        }

        if ($this->isActionRequest()) {
            if ($this->_POST['set_retake']) {
                $assignedTest->assignRetake($uid, false, true);
            }

            if ($assignedTest->hasErrors()) {
                $this->setErrors($assignedTest->getErrors());
            } else {
                Site::redirect(Site::getModurl() . '/' . $this->vars['group_code'] . '/' . $this->vars['subject_code'] . '/' . $this->vars['atid']);
            }
        }
    }
    
    public function retakeGroupAction($atid, $groupCode)
    {
        $group = $this->subjectsAction($groupCode);
        if ($this->hasErrors(ERROR_ELEMENT_NOT_FOUND)) {
            $this->setData(null);
            return;
        }

        $assignedTest = new Assignment(User::user()->getUID(), $atid);

        if ($assignedTest->hasErrors()) {
            $this->setErrors($assignedTest->getErrors(), ERROR_ELEMENT_NOT_FOUND);
            return;
        }

        if ($this->isActionRequest()) {
            if ($this->_POST['set_retake']) {
                $assignedTest->assignRetake($group['id'], true, $this->_POST['force']);
            }

            if ($assignedTest->hasErrors()) {
                $this->setErrors($assignedTest->getErrors());
            } else {
                Site::redirect(Site::getModurl() . '/' . $this->vars['group_code'] . '/' . $this->vars['subject_code']);
            }
        }
    }
}
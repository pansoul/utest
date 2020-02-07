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
            $v = $this->_POST;


            if ($assignedTest->hasErrors()) {
                $this->setErrors($assignedTest->getErrors());
            } else {
                Site::redirect(Site::getModurl() . '/for/' . $this->vars['group_code'] . '/' . $this->vars['subject_code']);
            }
        }

        /*if (!($tid && $uid))
            return;
        
        $sql = "
            SELECT t.*, 
                g.alias as group_alias, 
                g.title as group_title, 
                s.alias as subject_alias, 
                s.title as subject_title, 
                st.title as test_title, 
                u.name as u_name, 
                u.last_name as u_last_name, 
                u.surname as u_surname
            FROM {$this->table_student_passage} AS t 
            LEFT JOIN {$this->table_user} AS u 
                ON (u.id = t.user_id)
            LEFT JOIN {$this->table_group} AS g
                ON (g.id = u.group_id)
            LEFT JOIN {$this->table_student_test} AS st
                ON (st.id = t.test_id)
            LEFT JOIN {$this->table_subject} AS s
                ON (s.id = st.subject_id)
            WHERE
                t.test_id = {$tid}
                AND t.user_id = {$uid}
        ";
        $res = R::getRow($sql);          
        
        $url = USite::getModurl() . '/for/' . $res['group_alias'];
        UAppBuilder::addBreadcrumb($res['group_title'], $url);
        
        $url .= '/' . $res['subject_alias'];
        UAppBuilder::addBreadcrumb($res['subject_title'], $url);
        
        $url .= '/' . $res['test_id'];
        UAppBuilder::addBreadcrumb($res['test_title'], $url);
        
        // Запрос на изменение
        if ($this->request->_POST['a']) {
            $v = $this->request->_POST;
            
            if ($v['set_retake']) {
                $dataRow = R::findOne($this->table_student_passage, 'test_id = :tid AND user_id = :uid', array(
                    ':tid' => $tid,
                    ':uid' => $uid
                ));
                
                $dataRow->retake = $dataRow->retake + 1;
                $dataRow->status = 0;
                $dataRow->last_q_number = null;                
                R::store($dataRow);
            }
            USite::redirect($url);
        }
        
        return $this->returnResult($res);*/
    }
    
    public function retakeGroupAction($tid, $gid)
    {   
        if (!($tid && $gid))
            return;
        
        $sql = "
            SELECT t.*, 
                g.alias as group_alias, 
                g.title as group_title, 
                s.alias as subject_alias, 
                s.title as subject_title
            FROM {$this->table_student_test} AS t 
            LEFT JOIN {$this->table_group} AS g
                ON (g.id = t.group_id)
            LEFT JOIN {$this->table_subject} AS s
                ON (s.id = t.subject_id)
            WHERE
                t.id = {$tid}
                AND t.group_id = {$gid}
                AND t.user_id = " . UUser::user()->getUID() . "
        ";
        $res = R::getRow($sql);        
        
        $url = USite::getModurl() . '/for/' . $res['group_alias'];
        UAppBuilder::addBreadcrumb($res['group_title'], $url);
        
        $url .= '/' . $res['subject_alias'];
        UAppBuilder::addBreadcrumb($res['subject_title'], $url);
        
        // Запрос на изменение
        if ($this->request->_POST['a']) {
            $v = $this->request->_POST;
            
            if ($v['set_retake']) {
                $sql = "
                    SELECT t.*
                    FROM {$this->table_student_passage} AS t
                    LEFT JOIN {$this->table_user} AS u
                        ON (t.user_id = u.id)
                    WHERE
                        u.group_id = {$gid}
                        AND t.test_id = {$tid}
                ";                   
                $arUsers = R::getAll($sql); 

                foreach ($arUsers as $u)
                {
                    if ($u['status'] == 2) {
                        $dataRow = R::load($this->table_student_passage, $u['id']);
                        
                        $dataRow->retake = $dataRow->retake + 1;
                        $dataRow->status = 0;
                        $dataRow->last_q_number = null;
                        R::store($dataRow);
                    }
                }
            }
            USite::redirect($url);
        }
        
        return $this->returnResult($res);
    }

}
<?php

class StudentResultsModel extends ComponentModel {
    
    private $table_student_passage = 'u_student_test_passage';
    private $table_student_test = 'u_student_test';
    private $table_student_time = 'u_student_test_time';
    private $table_test = 'u_test';
    private $table_subject = 'u_prepod_subject';

    public function testlistAction()
    {   
        $sql = "
            SELECT r.*, s.title as test_title, j.title as subject, i.date_finish
            FROM {$this->table_student_passage} AS r 
            LEFT JOIN {$this->table_student_test} AS s 
                ON (s.id = r.test_id)
            LEFT JOIN {$this->table_test} AS t 
                ON (t.id = s.test_id)
            LEFT JOIN {$this->table_subject} AS j 
                ON (j.id = t.subject_id)
            LEFT JOIN {$this->table_student_time} AS i 
                ON (i.test_id = s.id)
            WHERE
                r.user_id = " . UUser::user()->getUID() . "
                AND r.status = 2
                AND i.retake_value = r.retake
                AND i.user_id = " . UUser::user()->getUID() . "
            ORDER BY i.date_finish DESC
        ";
        $res = R::getAll($sql);  

        if ($this->vars['tid']) {
            $test = new UTResult($this->vars['tid']);                          
            $tprop = $test->getTProp();
            $answer = $test->getResult($tprop['test_show_true'], isset($this->request->_GET['retake']) ? intval($this->request->_GET['retake']) : null);
            // it's not bag! After getResult with custom 'retake' we must to update the $tprop for correct date_start and date_finish
            $tprop = $test->getTProp();
            $this->errors = UTResult::$last_errors;
            
            if (!$test)
                $res = array();
            else {
                UAppBuilder::addBreadcrumb($tprop['test_title'], USite::getUrl());
                
                $res = array(
                    'test' => $tprop,
                    'answer' => $answer
                );
            }
        }

        return $this->returnResult($res);
    }

}
<?php

class StudentTestsModel extends UModel {
    
    private $table_subject = 'u_prepod_subject';
    private $table_student_test = 'u_student_test';
    private $table_user = 'u_user';
    private $table_question = 'u_test_question';
    private $table_student_passage = 'u_student_test_passage';

    public function myAction()
    {
        $this->errors = array();        
        $u = UUser::user()->getFields('*');
        
        // Находим дисциплины с доступными тестами для группы, к которой 
        // относится текущий студент
        $sql = "
            SELECT m.*, u.last_name as prepod_last_name, u.name as prepod_name, u.surname as prepod_surname
            FROM {$this->table_subject} AS m 
            LEFT JOIN {$this->table_student_test} AS s 
                ON (s.subject_id = m.id)
            LEFT JOIN {$this->table_user} AS u 
                ON (m.user_id = u.id)
            WHERE
                s.group_id = {$u['group_id']}
            ORDER BY m.title
        ";                  
        $records = R::getAll($sql);
        $res = R::convertToBeans($this->table_student_test, $records);
        
        // Включим в свойства дисциплины информацию о количестве тестов
        foreach ($res as &$item)
        {
            $item['test_count'] = R::count($this->table_student_test, 'subject_id = :sid AND user_id = :uid ', array(
                        ':sid' => $item['id'],
                        ':uid' => $item['user_id']
            ));
        }

        // если есть данные о выбранном предмете
        if ($this->vars['subject_code']) {
            $sparent = R::findOne($this->table_subject, '`alias` = ?', array($this->vars['subject_code']));
            
            // предмет найден
            if ($sparent) {
                UAppBuilder::addBreadcrumb($sparent['title'], USite::getModurl() . '/my/' . $sparent['alias']);
                
                // если есть данные о выбранном тесте
                if ($this->vars['tid']) {                                       
                    $tparent = R::findOne($this->table_student_test, 'id = :id AND group_id = :gid', array(
                                ':id' => $this->vars['tid'],
                                ':gid' => $u['group_id']
                    ));
                    
                    // тест найден
                    if ($tparent) {
                        UAppBuilder::addBreadcrumb($tparent['title'], USite::getUrl());
                        if ($tparent['count_q'] == 0) {
                            $tparent['count_q'] = R::count($this->table_question, 'test_id = ?', array($tparent['test_id']));
                        }
                        if (UUser::user()->checkRunningTest($tparent['id'])) {                            
                            $test = new Test($tparent['id'], UUser::user()->getUID());
                            
                            if (!empty(Test::$last_errors))
                                $this->errors = Test::$last_errors;
                            
                            $res = array(
                                'tparent' => $tparent,
                                'q' => empty($this->errors) ? $test->gotoQuestion(LAST_Q) : ''
                            );
                        } else {
                            $res = $tparent;
                        }
                    } else
                        $res = array();
                } 
                // Выбираем список доступных тестов                
                else {
                    $sql = "
                        SELECT t.*, p.status, p.retake
                        FROM {$this->table_student_test} AS t
                        LEFT JOIN {$this->table_student_passage} AS p 
                            ON (p.test_id = t.id AND p.user_id = " . UUser::user()->getUID() . ")
                        WHERE
                            t.subject_id = {$sparent['id']}
                            AND t.group_id = {$u['group_id']}                            
                        ORDER BY t.title
                    ";                  
                    $records = R::getAll($sql);                    
                    $res = R::convertToBeans($this->table_student_test, $records);
                    
                    foreach ($res as &$item)
                    {
                        if ($item['count_q'] == 0) {
                            $item['count_q'] = R::count($this->table_question, 'test_id = ?', array($item['test_id']));
                        }
                        $item['status'] = is_null($item['status']) ? 0 : $item['status'];
                        $item['retake'] = is_null($item['retake']) ? 0 : $item['retake'];
                    }
                }
            } else 
                $res = array();
        }
        
        return $this->returnResult($res);
    }
    
    public function runAction() 
    {
        $res = array();
        
        if (!$this->request->isAjaxRequest())
            USite::redirect(USite::getModurl());
        
        $test = new Test($this->vars['id'], UUser::user()->getUID());   
        
        if (!empty(Test::$last_errors)) {
            $res['status'] = 'ERROR';
            $res['status_message'] = Test::$last_errors;
            header('Content-Type: application/json');
            return json_encode($res);            
        }
        
        $res = $test->gotoQuestion(LAST_Q); 
        header('Content-Type: application/json');
        return json_encode($res);      
    }
    
    public function qAction()
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
        
        $res = $test->gotoQuestion($this->vars['num']);
        header('Content-Type: application/json');
        return json_encode($res);      
    }
    
    public function endAction()
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
    }

}
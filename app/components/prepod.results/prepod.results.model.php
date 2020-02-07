<?php

class PrepodResultsModel extends UModel {

    private $table_subject = 'u_prepod_subject';
    private $table_test = 'u_test';
    private $table_group = 'u_univer_group';
    private $table_student_test = 'u_student_test';
    private $table_user = 'u_user';
    private $table_student_passage = 'u_student_test_passage';
    private $table_student_time = 'u_student_test_time';

    public function forAction()
    {   
        // если есть данные о выбранной группе
        if ($this->vars['group_code']) {
            $gparent = R::findOne($this->table_group, '`alias` = ?', array($this->vars['group_code']));

            // группа найдена
            if ($gparent) {
                UAppBuilder::addBreadcrumb($gparent['title'], USite::getModurl() . '/for/' . $gparent['alias']);

                // если есть данные о выбранном предмете
                if ($this->vars['subject_code']) {
                    $sparent = R::findOne($this->table_subject, '`alias` = :alias AND user_id = :uid ', array(
                                ':alias' => $this->vars['subject_code'],
                                ':uid' => UUser::user()->getUID()
                    ));

                    // предмет найден
                    if ($sparent) {
                        UAppBuilder::addBreadcrumb($sparent['title'], USite::getModurl() . '/for/' . $gparent['alias'] . '/' . $sparent['alias']);
                        
                        // Если есть Id о выбранном тесте
                        if ($this->vars['tid']) {
                            $tparent = R::findOne($this->table_student_test, 'id = :tid AND user_id = :uid AND group_id = :gid AND subject_id = :sid', array(
                                        ':tid' => $this->vars['tid'],
                                        ':uid' => UUser::user()->getUID(),
                                        ':gid' => $gparent['id'],
                                        ':sid' => $sparent['id']
                            ));
                            
                            // Назначенный тест найден
                            if ($tparent) {
                                UAppBuilder::addBreadcrumb($tparent['title'], USite::getUrl());
                                
                                $sql = "
                                    SELECT u.*, t.status as test_status, t.retake as retake_value
                                    FROM {$this->table_user} AS u 
                                    LEFT JOIN {$this->table_student_passage} AS t 
                                        ON (t.user_id = u.id AND t.test_id = {$tparent['id']})
                                    WHERE
                                        u.group_id = {$gparent['id']}
                                    ORDER BY u.last_name
                                ";   
                                $res = R::getAll($sql);
                                
                                foreach ($res as &$u) {
                                    $u['test_status'] = is_null($u['test_status']) ? 0 : (int)$u['test_status'];
                                    $u['retake_value'] = is_null($u['retake_value']) ? 0 : (int)$u['retake_value'];
                                }
                            }
                        }
                        // Выводим списк назначенных тестов выбранной группе по выбранному предмету
                        else {
                            $sql = "
                                SELECT *
                                FROM {$this->table_student_test}
                                WHERE
                                    group_id = {$gparent['id']}
                                    AND subject_id = {$sparent['id']}
                                    AND user_id = " . UUser::user()->getUID() . "                                
                                ORDER BY date DESC
                            ";                   
                            $res = R::getAll($sql);                            

                            $_list = R::find($this->table_test, 'subject_id = :sid AND user_id = :uid ', array(
                                        ':sid' => $sparent['id'],
                                        ':uid' => UUser::user()->getUID()
                            ));
                            $tList = array();
                            foreach ($_list as $k => $j)
                            {
                                $tList[$k] = $j['title'];
                            }
                            
                            $res = array(
                                'form' => $res,
                                'test_list' => $tList,
                                'group_id' => $gparent['id']
                            );
                        }
                    }
                }
                // Выводим список предметов
                else {
                    $res = R::find($this->table_subject, 'user_id = ? ORDER BY title', array(UUser::user()->getUID()));
                    foreach ($res as &$item)                    
                    {
                        $item['test_count'] = R::count($this->table_student_test, 'subject_id = :sid AND user_id = :uid AND group_id = :gid', array(
                                    ':sid' => $item['id'],
                                    ':uid' => UUser::user()->getUID(),
                                    ':gid' => $gparent['id']
                        ));
                    }
                }
            }
        }
        // Выбор групп
        else {
            $res = R::findAll($this->table_group, 'ORDER BY title');
        }
        return $this->returnResult($res);
    }
    
    public function sRetakeAction($tid, $uid)
    {   
        if (!($tid && $uid))
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
        if ($this->request->_post['a']) {
            $v = $this->request->_post;
            
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
        
        return $this->returnResult($res);
    }
    
    public function gRetakeAction($tid, $gid)
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
        if ($this->request->_post['a']) {
            $v = $this->request->_post;
            
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
    
    public function rAction($tid, $uid)
    {
        if (!($tid && $uid))
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
        
        $test = new UTResult($tid, true, $uid);                            
        $tprop = $test->getTProp();
        $answer = $test->getResult(true, isset($this->request->_get['retake']) ? intval($this->request->_get['retake']) : null, $uid);
        // it's not bag! After getResult with custom 'retake' we must to update the $tprop for correct date_start and date_finish
        $tprop = $test->getTProp();
        $this->errors = UTResult::$last_errors;

        if (!$test)
            $res = array();
        else {
            UAppBuilder::addBreadcrumb('Результаты теста', USite::getUrl());
            $res = array(
                'test' => $tprop,
                'answer' => $answer,
                'user' => $res['u_last_name'].' '.$res['u_name'].' '.$res['u_surname']
            );
        }
        
        return $this->returnResult($res);
    }

}
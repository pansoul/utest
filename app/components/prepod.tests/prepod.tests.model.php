<?php

class PrepodTestsModel extends UModel {

    private $table_subject = 'u_prepod_subject';
    private $table_test = 'u_test';
    private $table_group = 'u_univer_group';
    private $table_student_test = 'u_student_test';

    public function myAction()
    {
        if ($this->request->_post['del_all']) {
            // Удаление вопросов в выбранном тесте
            if ($this->vars['tid']) {
                foreach ($this->request->_post['i'] as $item)
                {
                    if (!$item)
                        continue;

                    UUser::user()->deleteQuestion($this->vars['tid'], $item);
                }
            } 
            // Удаление тестов в выбранном предмете
            elseif ($this->vars['subject_code']) {
                foreach ($this->request->_post['i'] as $item)
                {
                    if (!$item)
                        continue;

                    UUser::user()->deleteTest($item);
                }
            }
            USite::redirect(USite::getUrl());
        }

        $res = R::find($this->table_subject, 'user_id = ? ORDER BY title', array(UUser::user()->getUID()));
        foreach ($res as &$item)
        {
            $item['test_count'] = R::count($this->table_test, 'subject_id = :sid AND user_id = :uid ', array(
                        ':sid' => $item['id'],
                        ':uid' => UUser::user()->getUID()
            ));
        }

        // если есть данные о выбранном предмете
        if ($this->vars['subject_code']) {
            $sparent = R::findOne($this->table_subject, '`alias` = :alias AND user_id = :uid ', array(
                        ':alias' => $this->vars['subject_code'],
                        ':uid' => UUser::user()->getUID()
            ));
            
            // предмет найден
            if ($sparent) {
                UAppBuilder::addBreadcrumb($sparent['title'], USite::getModurl() . '/my/' . $sparent['alias']);
                
                // если есть данные о выбранном тесте
                if ($this->vars['tid']) {
                    $tparent = R::findOne($this->table_test, 'id = :id AND user_id = :uid', array(
                                ':id' => $this->vars['tid'],
                                ':uid' => UUser::user()->getUID()
                    ));
                    
                    // тест найден
                    if ($tparent) {
                        UAppBuilder::addBreadcrumb($tparent['title'], USite::getUrl());
                        $res = UUser::user()->getQuestionList($tparent['id']);
                    }
                } else
                    $res = UUser::user()->getTestList($sparent['id']);
                
            }
        }

        return $this->returnResult($res);
    }

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
                        UAppBuilder::addBreadcrumb($sparent['title'], USite::getUrl());

                        if ($this->request->_post['del_all']) {
                            foreach ($this->request->_post['i'] as $item)
                            {
                                if (!$item)
                                    continue;
                                
                                $res = R::findOne($this->table_student_test, 'user_id = :uid AND `id` = :id', array(
                                    ':uid' => UUser::user()->getUID(),
                                    ':id' => $item
                                ));
                                R::trash($res);
                            }
                            USite::redirect(USite::getUrl());
                        }

                        $sql = "
                            SELECT *
                            FROM {$this->table_student_test}
                            WHERE
                                group_id = {$gparent['id']}
                                AND subject_id = {$sparent['id']}
                                AND user_id = " . UUser::user()->getUID() . "                                
                            ORDER BY date DESC
                        ";                   
                        $records = R::getAll($sql);
                        $res = R::convertToBeans($this->table_student_material, $records);
                        
                        $_list = R::find($this->table_test, 'subject_id = :sid AND user_id = :uid ', array(
                                    ':sid' => $sparent['id'],
                                    ':uid' => UUser::user()->getUID()
                        ));
                        $tList = array();
                        foreach ($_list as $k => $j)
                        {
                            $tList[$k] = $j['title'];
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
        return $this->returnResult(array(
                    'form' => $res,
                    'test_list' => $tList
        ));
    }

    public function newMyTestAction($v = array())
    {
        $this->errors = array();
        if ($this->vars['in']) {
            $r = R::findOne($this->table_subject, 'user_id = :uid AND `alias` = :alias', array(
                        ':uid' => UUser::user()->getUID(),
                        ':alias' => $this->vars['in']
            ));
            if ($r) {
                $v['subject_id'] = $r['id'];
                $in = '/my/' . $r['alias'];
                UAppBuilder::addBreadcrumb($r['title'], USite::getModurl() . '/my/' . $r['alias']);
            }
        } elseif ($this->vars['id']) {
            $_r = R::load($this->table_test, $this->vars['id']);
            $r = R::load($this->table_subject, $_r['subject_id']);
            if ($r) {
                $in = '/my/' . $r['alias'];
                UAppBuilder::addBreadcrumb($r['title'], USite::getModurl() . $in);
            }
        }
        if ($this->request->_post['a']) {
            $v = $this->request->_post;

            $result = UUser::user()->createTest($v);
            if ($result)
                USite::redirect(USite::getModurl() . $in);
                
            $this->errors = UUser::$last_errors;  
        }
        $_list = R::find($this->table_subject, 'user_id = ?', array(UUser::user()->getUID()));
        $sList = array();
        foreach ($_list as $k => $j)
        {
            $sList[$k] = $j['title'];
        }
        return $this->returnResult(array(
                    'form' => $v,
                    'subject_list' => $sList
        ));
    }
    
    public function newMyQuestionAction($v = array())
    {
        $this->errors = array();    
        
        // Если есть переменная о значении теста, в котором будет создание вопроса
        if ($this->vars['in_tid']) {
            $sql = "
                SELECT t.*, s.alias, s.title as subject_title
                FROM {$this->table_test} AS t 
                LEFT JOIN {$this->table_subject} AS s 
                    ON (t.subject_id = s.id)
                WHERE
                    t.id = {$this->vars['in_tid']}
                    AND t.user_id = " . UUser::user()->getUID() . "                                
            ";
            $r = R::getRow($sql);
            
            if ($r) {
                $tid = $r['id'];
                $in = '/my/' . $r['alias'];
                UAppBuilder::addBreadcrumb($r['subject_title'], USite::getModurl() . $in);
                $in .= '/test-' . $tid;
                UAppBuilder::addBreadcrumb($r['title'], USite::getModurl() . $in);
            }
        } 
        // Если же есть Id загруженного вопроса
        elseif ($this->vars['id']) {
            $_r = R::load($this->table_test, $this->vars['id']);
            $r = R::load($this->table_subject, $_r['subject_id']);
            if ($r) {
                $in = '/my/' . $r['alias'];
                UAppBuilder::addBreadcrumb($r['title'], USite::getModurl() . $in);
            }
        }
        
        // Есть запрос отправки формы
        if ($this->request->_post['a']) {
            $v = $this->request->_post;  
            
            if ($v['question']['id']) {              
                $result = UUser::user()->editQuestion(
                    $tid, 
                    $v['question']['id'], 
                    $v['question'], 
                    $v['answer'], 
                    $v['right_answer']
                );
                
                if ($result)
                    USite::redirect(USite::getModurl() . $in);

                $this->errors = UUser::$last_errors;
            } else {
                $result = UUser::user()->createQuestion(
                    $tid, 
                    $v['question'], 
                    $v['answer'], 
                    $v['right_answer']
                );
                
                if ($result)
                    USite::redirect(USite::getModurl() . $in);

                $this->errors = UUser::$last_errors;
            }
        }
        
        $_list = Test::getTypeQuestion();        
        $tList = array();
        foreach ($_list as $k => $j)
        {
            $tList[$k] = $j['name'];
        }
        return $this->returnResult(array(
                    'form_question' => $v['question'],
                    'question_type_list' => $tList,
                    'form_answer' => $v['answer'],
                    'form_right' => $v['right_answer']
            
        ));
    }

    public function newForAction($v = array())
    {
        $this->errors = array();
        
        if ($this->vars['id']) {
            $this->vars['group_code'] = $v['group_code'];
            $this->vars['subject_code'] = $v['subject_code'];
        }
        
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
                        $in = '/for/' . $gparent['alias'] . '/' . $sparent['alias'];
                        UAppBuilder::addBreadcrumb($sparent['title'], USite::getModurl() . $in);
                        
                        $_list = R::find($this->table_test, 'subject_id = :sid AND user_id = :uid ', array(
                                    ':sid' => $sparent['id'],
                                    ':uid' => UUser::user()->getUID()
                        ));
                        $tList = array();
                        foreach ($_list as $k => $j)
                        {
                            $tList[$k] = $j['title'];
                        }

                        // Запрос на изменение
                        if ($this->request->_post['a']) {
                            $v = $this->request->_post;

                            if (!$this->vars['id']) {
                                if (!$v['test_id'])
                                    $this->errors[] = "Укажите основу теста";

                                $v['count_q'] = abs(intval($v['count_q']));
                            }

                            if (empty($this->errors)) {
                                $r = R::findOrDispense($this->table_student_test, 'id = ?', array($v['id']));
                                $dataRow = reset($r);
                                $dataRow->title = $v['title'] ? $v['title'] : $tList[ $v['test_id'] ];
                                $dataRow->is_mixing = $v['is_mixing'];
                                $dataRow->is_show_true = $v['is_show_true'];
                                if (!$dataRow->id) {
                                    $dataRow->date = UAppBuilder::getDateTime();
                                    $dataRow->group_id = $gparent['id'];
                                    $dataRow->subject_id = $sparent['id'];
                                    $dataRow->test_id = $v['test_id'];
                                    $dataRow->user_id = UUser::user()->getUID();
                                    $dataRow->count_q = $v['count_q'];                                                                        
                                }
                                if (R::store($dataRow))
                                    USite::redirect(USite::getModurl() . $in);
                            }
                        }
                    }
                }
            }
        }
        return $this->returnResult(array(
                    'form' => $v,
                    'test_list' => $tList
        ));
    }

    public function editMyTestAction($id)
    {
        if (!$id)
            return;

        $v = R::findOne($this->table_test, 'id = :id AND user_id = :uid ', array(
                    ':id' => $id,
                    ':uid' => UUser::user()->getUID()
        ));
        return $this->newMyTestAction($v);
    }
    
    public function editMyQuestionAction($tid, $id)
    {
        if (!($id && $tid))
            return;
        
        $v = UUser::user()->_prepareEditQuestion($tid, $id);
        
        return $this->newMyQuestionAction($v);
    }

    public function editForTestAction($id)
    {
        if (!$id)
            return;
        
        $sql = "
            SELECT t.*, g.alias as group_code, s.alias as subject_code
            FROM {$this->table_student_test} AS t 
            LEFT JOIN {$this->table_group} AS g 
                ON (g.id = t.group_id)
            LEFT JOIN {$this->table_subject} AS s 
                ON (s.id = t.subject_id)
            WHERE
                t.id = {$id}
                AND t.user_id = " . UUser::user()->getUID() . "
        ";
        $record = R::getAll($sql);
        $res = R::convertToBeans($this->table_student_test, $record);
        $v = reset($res);
        
        return $this->newForAction($v);
    }
    
    public function newTypeAction()
    {
        if (!$this->request->isAjaxRequest())
            return null;
        else
            return $this->returnResult();
    }
    
    public function delAnswerAction($tid, $qid, $aid)
    {
        $res = array();
        
        if (!$this->request->isAjaxRequest())
            return null;
        
        if (!($tid && $qid && $aid)) {
            $res['status'] = 'OK';
            header('Content-Type: application/json');
            return json_encode($res);
        }
        
        $test = new Test($tid, UUser::user()->getUID(), true);
        
        if (!empty(Test::$last_errors)) {
            $res['status'] = 'ERROR';
            $res['status_message'] = Test::$last_errors;
            header('Content-Type: application/json');
            return json_encode($res);
        }
        
        $test->loadFullQuestion($qid);
        
        if (!empty(Test::$last_errors)) {
            $res['status'] = 'ERROR';
            $res['status_message'] = Test::$last_errors;
            header('Content-Type: application/json');
            return json_encode($res);
        }
        
        $test->deleteAnswer($aid);
        
        if (!empty(Test::$last_errors)) {
            $res['status'] = 'ERROR';
            $res['status_message'] = Test::$last_errors;
            header('Content-Type: application/json');
            return json_encode($res);
        }
        
        $res['status'] = 'OK';
        header('Content-Type: application/json');
        return json_encode($res);
    }
    
    public function delQuestionAction($tid, $qid)
    {
        if (!($tid && $qid))
            return;
        
        UUser::user()->deleteQuestion($tid, $qid);
        
        if (!empty(UUser::$last_errors))
            USite::redirect(USite::getModurl());
        
        //$beanDeleted = $test->deleteQuestion();        
        
        $sql = "
            SELECT t.*, s.alias as subject_code
            FROM {$this->table_test} AS t 
            LEFT JOIN {$this->table_subject} AS s 
                ON (s.id = t.subject_id)
            WHERE
                t.id = {$tid}
        ";
        $record = R::getRow($sql);
        $toback  = '/my/' . $record['subject_code'] . '/test-' . $record['id'];
        USite::redirect(USite::getModurl() . $toback);
    }

    public function deleteAction($type, $id)
    {
        if (!($type && $id))
            return;

        if ($type == 'mytest') {
            $beanDeleted = UUser::user()->deleteTest($id);
            $subject = R::load($this->table_subject, $beanDeleted['subject_id']);
            $toback = '/my/' . $subject['alias'];
        } elseif ($type == 'fortest') {
            $sql = "
                SELECT t.*, g.alias as group_code, s.alias as subject_code
                FROM {$this->table_student_test} AS t 
                LEFT JOIN {$this->table_group} AS g 
                    ON (g.id = t.group_id)
                LEFT JOIN {$this->table_subject} AS s 
                    ON (s.id = t.subject_id)
                WHERE
                    t.id = {$id}
                    AND t.user_id = " . UUser::user()->getUID() . "
            ";
            $record = R::getAll($sql);
            $res = R::convertToBeans($this->table_student_test, $record);
            $bean = reset($res);
            R::trash($bean);

            $toback = '/for/' . $bean['group_code'] . '/' . $bean['subject_code'];
        }
        
        USite::redirect(USite::getModurl() . $toback);
    }

}
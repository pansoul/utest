<?php

class UTResult {
    
    private $tid;  
    private $studentId;
    private $arTProp = array();    
    private $retake = 0;
    private $retakeReal = 0;
    
    private $table_student_passage = 'u_student_test_passage';
    private $table_student_test = 'u_student_test';
    private $table_student_time = 'u_student_test_time';
    private $table_test = 'u_test';
    private $table_subject = 'u_prepod_subject';
    private $table_st_answer = 'u_student_test_answer';
    private $table_question = 'u_test_question';
    
    public static $last_errors = array();

    public function __construct($tid, $retake = null, $isPrepod = false, $studentId = 0, $strict = true)
    {        
        self::$last_errors = array();
        $studentId = intval($studentId);        
        
        if ($isPrepod) {
            $sql = "
                SELECT r.*, s.title as test_title, j.title as subject, s.is_show_true, t.id as parent_tid, i.date_start, i.date_finish
                FROM {$this->table_student_passage} AS r 
                LEFT JOIN {$this->table_student_test} AS s 
                    ON (s.id = r.test_id)
                LEFT JOIN {$this->table_test} AS t 
                    ON (t.id = s.test_id)
                LEFT JOIN {$this->table_subject} AS j 
                    ON (j.id = t.subject_id)
                LEFT JOIN {$this->table_student_time} AS i
                    ON (i.test_id = s.id AND i.user_id = {$studentId} AND i.retake_value = " . (is_null($retake) ? 'r.retake' : intval($retake)) . ")
                WHERE
                    r.user_id = {$studentId}                    
                    AND r.test_id = {$tid}
                    AND s.user_id = " . UUser::user()->getUID() . "
            ";
            $res = R::getRow($sql);              
            
            // Если к этому моменту тест не найден, значит студент ещё не начинал его тестирования.
            // При указании $strict в false, будет искаться просто тест с общей информацией.
            if (!$res && !$strict) {                                
                $user = UUser::getById($studentId);                 
                $group = intval($user->group_id);
                $sql = "
                    SELECT s.*, s.title as test_title, j.title as subject, s.test_id as parent_tid, i.date_start, i.date_finish
                    FROM {$this->table_student_test} AS s   
                    LEFT JOIN {$this->table_subject} AS j 
                        ON (j.id = s.subject_id)   
                    LEFT JOIN {$this->table_student_time} AS i
                        ON (i.test_id = s.id AND i.user_id = {$studentId})                    
                    WHERE
                        s.id = {$tid}                    
                        AND s.group_id = {$group}
                        AND s.user_id = " . UUser::user()->getUID() . "
                ";
                $res = R::getRow($sql);
                if ($res) {
                    $res['status'] = 0;
                    $res['date_start'] = null;
                    $res['date_finish'] = null;                
                }
            }
        } else {            
            $sql = "
                SELECT r.*, s.title as test_title, j.title as subject, s.is_show_true, t.id as parent_tid, i.date_start, i.date_finish
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
                    AND r.test_id = {$tid}
                    AND i.user_id = " . UUser::user()->getUID() . "
                    AND i.retake_value = r.retake
            ";
            $res = R::getRow($sql);
        }  
        
        if (!$res) {
            self::$last_errors = array('Тест не найден');
            return;
        }
        
        $this->tid = $tid;
        $this->studentId = $isPrepod ? $studentId : UUser::user()->getUID();
        $this->retake = is_null($retake) ? $res['retake'] : intval($retake);
        $this->retakeReal = $res['retake'];        
        $this->arTProp = array(
            'parent_tid' => $res['parent_tid'],
            'date_start' => $res['date_start'],
            'date_finish' => $res['date_finish'],
            'retake' => $this->retake,
            'retake_real' => $this->retakeReal,
            'test_title' => $res['test_title'],
            'test_show_true' => $res['is_show_true'],
            'subject' => $res['subject'],
            'status' => $this->retake == $this->retakeReal ? $res['status'] : 2
        );        
    }
    
    public function getTProp() 
    {
        return $this->arTProp;
    }
    
    public function getResult($showAnswer = false, $retake = null, $studentId = 0)
    {
        self::$last_errors = array();
        $studentId = $studentId ? intval($studentId) : $this->studentId;
        
        if (!$this->tid) {
            self::$last_errors = array('Не найден тест для показа результата');
            return false;
        }
        
        if (!$studentId) {
            self::$last_errors = array('Не указан пользователь для показа результата');
            return false;
        }
        
        if (null === $retake) {
            $retake = $this->retake;                     
        } else {
            $retake = intval($retake);    
            $dataTime = R::findOne($this->table_student_time, 'test_id = :tid AND user_id = :uid AND retake_value = :retake', array(
                ':tid' => $this->tid,
                ':uid' => $studentId,
                ':retake' => $retake
            ));
            $this->arTProp['date_start'] = $dataTime['date_start'];
            $this->arTProp['date_finish'] = $dataTime['date_finish'];
        }
        
        $answer = array();
        $trueQCount = 0;
        
        $arUserQ = R::find($this->table_st_answer, 'user_id = :uid AND test_id = :tid AND retake_value = :retake ORDER BY number', array(
            ':uid' => $studentId,
            ':tid' => $this->tid,
            ':retake' => $retake
        )); 
        
        if (!$arUserQ) {
            self::$last_errors = array('Не найдены ответы пользователя');
            return false;
        }
        
        $parent = R::load($this->table_student_test, $this->tid);
        // @todo allQCount
        $allQCount = $parent['count_q'] ? $parent['count_q'] : R::count($this->table_question, 'test_id = ?', array($this->arTProp['parent_tid']));
        
        foreach ($arUserQ as $question)
        {            
            $uA = unserialize($question['user_answer']);
            $q = unserialize($question['q']);
            $isRight = false;
            
            if ($q['type'] == 'one') {
                if ($q['right'][$uA]) {
                    $trueQCount++;
                    $isRight = true;
                }
            } elseif ($q['type'] == 'match') {
                if ($this->mb_strcasecmp($uA, $q['right'], 'utf-8') == 0) {
                    $trueQCount++;
                    $isRight = true;
                }
            } else {
                if ($q['right'] == $uA) {
                    $trueQCount++;
                    $isRight = true;
                }
            }
            
            if ($showAnswer) {
                $answer[$question['number']] = $q;
                $answer[$question['number']]['user_answer'] = $uA;
                $answer[$question['number']]['is_right'] = $isRight;
            }
        }    
        
        $result = array(
            'all_q' => $allQCount,
            'true_q' => $trueQCount,
            'percent_passage' => round(100 / $allQCount * $trueQCount, 1),
            'answer_list' => $answer
        );        
        return $result;
    }
    
    protected function mb_strcasecmp($str1, $str2, $encoding = null)
    {
        if (null === $encoding) {
            $encoding = mb_internal_encoding();
        }
        return strcmp(mb_strtoupper($str1, $encoding), mb_strtoupper($str2, $encoding));
    }
    
}
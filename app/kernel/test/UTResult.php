<?php

class UTResult {
    
    private $tid;    
    private $arTProp = array();
    private $retake = 0;
    
    private $table_student_passage = 'u_student_test_passage';
    private $table_student_test = 'u_student_test';
    private $table_student_time = 'u_student_test_time';
    private $table_test = 'u_test';
    private $table_subject = 'u_prepod_subject';
    private $table_st_answer = 'u_student_test_answer';
    private $table_question = 'u_test_question';
    
    public static $last_errors = array();

    public function __construct($tid, $isPrepod = false, $uid = 0)
    {
        self::$last_errors = array();
        
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
                    ON (i.test_id = s.id)
                WHERE
                    r.user_id = {$uid}
                    AND r.status = 2
                    AND r.test_id = {$tid}
                    AND s.user_id = " . UUser::user()->getUID() . "
                    AND i.user_id = {$uid}
                    AND i.retake_value = r.retake
            ";
            $res = R::getRow($sql);
            
            if (!$res) {
                self::$last_errors = array('Тест выбранного студента не найден');
                return false;
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
            
            if (!$res) {
                self::$last_errors = array('Тест не найден');
                return false;
            }
        }
        
        $this->tid = $tid;
        $this->retake = $res['retake'];
        $this->arTProp = array(
            'parent_tid' => $res['parent_tid'],
            'date_start' => $res['date_start'],
            'date_finish' => $res['date_finish'],
            'retake' => $res['retake'],
            'test_title' => $res['test_title'],
            'test_show_true' => $res['is_show_true'],
            'subject' => $res['subject']
        );
    }
    
    public function getTProp() 
    {
        return $this->arTProp;
    }
    
    public function getResult($showAnswer = false, $retake = null, $uid = 0)
    {
        self::$last_errors = array();
        $uid = intval($uid) ? intval($uid) : UUser::user()->getUID();
        
        if ($retake === null)
            $retake = $this->retake;                     
        else {
            $retake = intval($retake);    
            $dataTime = R::findOne($this->table_student_time, 'test_id = :tid AND user_id = :uid AND retake_value = :retake', array(
                ':tid' => $this->tid,
                ':uid' => $uid,
                ':retake' => $retake
            ));
            $this->arTProp['date_start'] = $dataTime['date_start'];
            $this->arTProp['date_finish'] = $dataTime['date_finish'];
        }
        
        $answer = array();
        $trueQCount = 0;
        
        if (!$uid) {
            self::$last_errors = array('Не найден пользователь для показа результата');
            return false;
        }
        
        $arUserQ = R::find($this->table_st_answer, 'user_id = :uid AND test_id = :tid AND retake_value = :retake ORDER BY number', array(
            ':uid' => $uid,
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
            'percent_passage' => round(100/$allQCount*$trueQCount, 1),
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
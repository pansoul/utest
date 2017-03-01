<?php

class Multiple extends AbstractType {
    
    public function __construct($qid = 0)
    {
        parent::__construct($qid);
    }
    
    public function validate($v = array(), $r = null)
    {
        $_e = array();
        
        foreach ($v as $k => $item)
        {
            if (!empty($item['title'])) {
                $this->validVariant[ $k ] = $item;                                
                if ($r[ $k ] == 1) {
                    $this->validRight[ $k ] = 1;
                }
            }
        }
        
        if (!count($this->validVariant)) {
            $_e[] = 'Необходимо заполнить варианты ответов';    
            list($key, $value) = each($v);
            $this->validVariant = array($k => $value);
        }
        
        if (!count($this->validRight)) {
            $_e[] = 'Не указаны верные ответы';        
        }
        
        if (!empty($_e)) {
            $this->last_error = $_e;
            return false;
        }
        
        return true;
    }
    
    public function save()
    {
        if (!$this->checkQuestionExists()) {
            return false;
        }
        
        foreach ($this->validVariant as $k => $item)
        {
            $res = R::findOrDispense(TABLE_TEST_ANSWER, 'id = :id AND question_id = :qid', array(
                ':id' => $item['id'],
                ':qid' => $this->qid
            ));
            
            $dataRow = reset($res); 
            
            if (!$dataRow->id) {
                $dataRow->question_id = $this->qid;
            }            
            
            $dataRow->title = $item['title'];
            $dataRow->right_answer = intval(isset($this->validRight[$k]));
            R::store($dataRow);
        }
        
        return true;
    }
    
}
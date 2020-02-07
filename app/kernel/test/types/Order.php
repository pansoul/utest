<?php

class Order extends AbstractType {
    
    public function __construct($qid = 0)
    {
        parent::__construct($qid);
    }
    
    public function validate(array $v = array(), $r = null)
    {
        $_e = array();
        
        foreach ($v as $k => $item)
        {
            if (empty($item['title'])) {
                $_e[] = 'Необходимо заполнить варианты ответов';    
                break;
            }
        }
        
        $this->validVariant = $v;        
        $r = array_unique($r);
        
        if (count($r) != count($this->validVariant))
            $_e[] = 'Верные позиции должны быть уникальны и не могут повторяться';
        
        if (!empty($_e)) {
            $this->last_error = $_e;
            return false;
        }
        
        $this->validRight = $r;
        return true;
    }
    
    public function save($qid = null)
    {
        if (intval($qid))
            $this->qid = intval($qid);
        
        foreach ($this->validVariant as $k => $item)
        {
            $res = R::findOrDispense($this->table_answer, 'id = :id AND question_id = :qid', array(
                        ':id' => $item['id'],
                        ':qid' => $this->qid
            ));
            
            $dataRow = reset($res);
            
            if (!$dataRow->id)
                $dataRow->question_id = $this->qid;
            
            $dataRow->title = $item['title'];
            $dataRow->right_answer = $this->validRight[$k];

            R::store($dataRow);
        }
    }
    
}
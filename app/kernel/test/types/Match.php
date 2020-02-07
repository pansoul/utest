<?php

class Match extends AbstractType {
    
    public function __construct($qid = 0)
    {
        parent::__construct($qid);
    }
    
    public function validate(array $r = array())
    {   
        $this->validVariant = $r;
        
        if (empty($r['right_answer'])) {
            $this->last_error = array('Не указано точное написание верного ответа');
            return false;
        }
        
        $this->validRight = $r;
        return true;
    }
    
    public function save($qid = null)
    {
        if (intval($qid))
            $this->qid = intval($qid);
        
        $res = R::findOrDispense($this->table_answer, 'id = :id AND question_id = :qid', array(
                    ':id' => $this->validRight['id'],
                    ':qid' => $this->qid
        ));

        $dataRow = reset($res);

        if (!$dataRow->id)
            $dataRow->question_id = $this->qid;

        $dataRow->right_answer = $this->validRight['right_answer'];

        R::store($dataRow);
    }
    
}
<?php

abstract class AbstractType {
    
    protected $qid;
    
    protected $arRequired = array();
    protected $validVariant;
    protected $validRight;

    protected $table_answer = 'u_test_answer';
    protected $table_question = 'u_test_question';
    
    public $last_error;

    public function __construct($qid = 0)
    {
        $this->qid = $qid;
    }
    
    abstract public function validate();
    
    abstract public function save();
    
    public function delete($aid) 
    {
        $bean = R::load($this->table_answer, $aid);
        
        if ($bean->id) {
            R::trash($bean);
            return true;
        } else {
            $this->last_error = array('Вариант ответа для удаления не найден');
            return false;
        }
    }
    
    public function getValidVariant()
    {
        return $this->validVariant;
    }
    
}

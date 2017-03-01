<?php

abstract class AbstractType {
    
    protected $qid;    
    
    protected $arRequired = array();
    protected $validVariant;
    protected $validRight;    
    
    public $last_error;

    public function __construct($qid = 0)
    {
        $this->qid = $qid;        
    }
    
    abstract public function validate();
    
    abstract public function save();
    
    public function delete($aid) 
    {
        $bean = R::findOne(TABLE_TEST_ANSWER, 'id = ? AND question_id = ?', array($aid, $this->qid));
        
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
    
    public function checkQuestionExists()
    {
        $bean = R::load(TABLE_TEST_QUESTION, $this->qid);
        if ($bean->id) {
            return true;
        } else {
            $this->last_error = array('Id вопроса не найден или указан неверно');
            return false;
        }
    }
    
}

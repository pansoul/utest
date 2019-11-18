<?php

class PrepodProfileModel extends ComponentModel {
    
    private $arAvailableEdit = array(
        'last_name',
        'name',
        'surname',
        'post',
        'phone',
        'email'
    );
    
    private $arPost = array(
        'old_prepod'    => 'старший преподаватель',
        'docent'        => 'доцент',
        'prof'          => 'профессор',
        'prepod'        => 'преподаватель',
    );
    
    public function indexAction()
    {   
        $v = UUser::user()->getFields($this->arAvailableEdit);
        
        if ($this->request->_POST['a']) {             
            $v = array_intersect_key($this->request->_POST, $v);              
            
            if (UUser::user()->doAction('admin', 'edit', array($v, UUser::user()->getUID()))) {
                $_SESSION['update'] = 'Y';
                USite::redirect (USite::getModurl());
            }
            
            $this->errors = UUser::$last_errors;
        }
        return $this->returnResult($v);
    }
    
    public function getArPost()
    {
        return $this->arPost;
    }
}

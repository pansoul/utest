<?php

class StudentProfileModel extends UModel {  
    
    private $arAvailableEdit = array(        
        'phone',
        'email'
    );    
    
    public function indexAction()
    {   
        $v = UUser::user()->getFields($this->arAvailableEdit);        
        
        if ($this->request->_post['a']) {             
            $v = array_intersect_key($this->request->_post, $v);     
            
            if (UUser::user()->doAction('admin', 'edit', array($v, UUser::user()->getUID()))) {
                $_SESSION['update'] = 'Y';
                USite::redirect (USite::getModurl());
            }
        }
        return $this->returnResult($v);
    }
}

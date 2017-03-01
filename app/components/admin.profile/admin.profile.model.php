<?php

class AdminProfileModel extends UModel {
    
    public function indexAction()
    {   
        if ($this->request->_POST['a']) {
            $arField = array('password' => $this->request->_POST['password']);
            
            if (empty($this->request->_POST['password'])) {
                $this->errors = array('Пароль не может быть пустым');
            } elseif (UUser::user()->edit($arField)) {
                $_SESSION['update'] = 'Y';
                USite::redirect (USite::getModurl());
            }
        }
        return $this->returnResult();
    }
}

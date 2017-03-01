<?php

class IndexModel extends UModel {
    
    public function indexAction()
    {
        if (UUser::isAuth()) {
            USite::redirect('/' . UUser::user()->getRoleRootGroup());
        }
        
        if (isset($this->request->_POST['a'])) {            
            $success = UUser::login($this->request->_POST['login'], $this->request->_POST['pass']);            
            if ($success) {
                USite::redirect('/' . UUser::user()->getRoleRootGroup(), false, 'Здравствуйте, ' . UUser::user()->getName() . '!');
            } else {
                $this->errors = UUser::$last_errors;
            }
        }
        return $this->returnResult();
    }
}

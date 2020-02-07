<?php

class IndexModel extends UModel {
    
    public function indexAction()
    {
        if (isset($_SESSION['u_uid']))
            USite::redirect('/' . UUser::user()->getRGroup() . '/');
        
        if (isset($this->request->_post['a'])) {            
            $success = UUser::login($this->request->_post['login'], $this->request->_post['pass']);            
            if ($success)
                USite::redirect('/' . UUser::user()->getRGroup() . '/', false, 'Здравствуйте, ' . UUser::user()->getName() . '!');
            else
                $this->errors = UUser::$last_errors;
        }
        return $this->returnResult();
    }
}

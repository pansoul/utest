<?php

class AdminPrepodsModel extends UModel {    
    
    private $arPost = array(
        'old_prepod'    => 'старший преподаватель',
        'docent'        => 'доцент',
        'prof'          => 'профессор',
        'prepod'        => 'преподаватель',
    );

    public function prepodAction()
    {
        if ($this->request->_POST['del_all']) {            
            foreach ($this->request->_POST['i'] as $item)
            {
                $res = R::load(TABLE_USER, $item);
                R::trash($res);
            }
            USite::redirect(USite::getUrl());
        } 
        elseif ($this->request->_POST['newpass_all']) {
            $users = array();
            foreach ($this->request->_POST['i'] as $id)
            {
                if (!intval($id)) {
                    continue;
                }
                
                $newpass = UUser::newPassword();
                $user = UUser::user()->edit(array('password' => $newpass), $id);
                if ($user) {
                    $users[] = $user;
                } else {
                    $this->errors = UUser::$last_errors;
                    break;
                }
            }
        }        
        
        $res = R::find(TABLE_USER, 'group_id IS NULL AND role != "admin" ORDER BY last_name');        
        
        return $this->returnResult(array(
            'form' => $res,
            'users' => $users
        ));
    }

    public function newPrepodAction($v = array())
    {              
        if ($this->request->_POST['a']) {
            $this->errors = array();  
            $v = $this->request->_POST;             
            if ($v['id']) {
                $user = UUser::user()->edit($v, $v['id']);
                if ($user && empty($v['password'])) {
                    USite::redirect(USite::getModurl());
                }
            } else {                
                $v['role'] = 'prepod';
                $user = UUser::user()->add($v);                
            }
            $this->errors = UUser::$last_errors;            
        }        
        return $this->returnResult(array(
            'form' => $v,                    
            'user' => $user
        ));
    }

    public function editAction($id)
    {   
        $v = R::load(TABLE_USER, $id);   
        if (UUser::getRootGroup($v['role']) !== 'prepod') {
            $this->setErrors('Пользователь не найден', ERROR_ELEMENT_NOT_FOUND);            
        }        
        return $this->newPrepodAction($v);
    }

    public function deleteAction($id)
    {
        if (!$id) {
            return;
        }
        
        $bean = R::load(TABLE_USER, $id);
        if (UUser::getRootGroup($bean['role']) === 'prepod') {
            R::trash($bean);            
        }
        USite::redirect(USite::getModurl());
    }
    
    public function getArPost()
    {
        return $this->arPost;
    }

}
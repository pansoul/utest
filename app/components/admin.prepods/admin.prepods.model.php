<?php

class AdminPrepodsModel extends UModel {
    
    private $table_user = 'u_user';
    private $table_roles = 'u_user_roles';
    
    private $arPost = array(
        'old_prepod'    => 'старший преподаватель',
        'docent'        => 'доцент',
        'prof'          => 'профессор',
        'prepod'        => 'преподаватель',
    );

    public function prepodAction()
    {
        if ($this->request->_post['del_all']) {            
            foreach ($this->request->_post['i'] as $item)
            {
                $res = R::load($this->table_user, $item);
                R::trash($res);
            }
            USite::redirect(USite::getUrl());
        } elseif ($this->request->_post['newpass_all']) {
            $users = array();
            foreach ($this->request->_post['i'] as $id)
            {
                if (!intval($id))
                    continue;
                
                $newpass = UUser::newPassword();
                $user = UUser::user()->edit(array('password' => $newpass), $id);
                if ($user)
                    $users[] = $user;
                else {
                    $this->errors = UUser::$last_errors;
                    break;
                }
            }
        }        
        
        $res = R::find($this->table_user, 'group_id IS NULL AND role != "admin" ORDER BY last_name');        
        
        return $this->returnResult(array(
            'form' => $res,
            'users' => $users
        ));
    }

    public function newPrepodAction($v = array())
    {
        $this->errors = array();        
        if ($this->request->_post['a']) {
            $v = $this->request->_post;      
            if ($v['id']) {
                $user = UUser::user()->edit($v, $v['id']);
                if ($user && empty($v['password']))
                    USite::redirect(USite::getModurl());
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
        if (!$id)
            return;
        
        $v = R::load($this->table_user, $id);   
        if (UUser::getRootGroup($v['role']) !== 'prepod')
            return;
        
        return $this->newPrepodAction($v);
    }

    public function deleteAction($id)
    {
        if (!$id)
            return;
        
        $bean = R::load($this->table_user, $id);
        if ($bean && UUser::getRootGroup($bean['role']) === 'prepod') {
            R::trash($bean);
            USite::redirect(USite::getModurl());
        } else 
            USite::redirect(USite::getModurl());
    }
    
    public function getArPost()
    {
        return $this->arPost;
    }

}
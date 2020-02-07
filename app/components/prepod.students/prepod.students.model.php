<?php

class PrepodStudentsModel extends UModel {

    private $table_group = 'u_univer_group';
    private $table_faculty = 'u_univer_faculty';
    private $table_speciality = 'u_univer_speciality';    
    private $table_user = 'u_user';
    private $table_roles = 'u_user_roles';    

    public function groupAction()
    {
        if ($this->request->_post['newpass_all']) {
            $users = array();
            foreach ($this->request->_post['i'] as $id)
            {
                if (!intval($id))
                    continue;
                
                $newpass = UUser::newPassword();
                $user = UUser::user()->doAction('admin', 'edit', array(array('password' => $newpass), $id));
                if ($user)
                    $users[] = $user;
                else {
                    $this->errors = UUser::$last_errors;
                    break;
                }
            }
        }
        
        $res = R::findAll($this->table_group, 'ORDER BY title');
        foreach ($res as &$item)
        {
            $spec = R::load($this->table_speciality, $item['speciality_id']);
            $item['speciality_name'] = $spec['title'];
            $item['students_count'] = R::count($this->table_user, "`group_id` = ?", (array)$item['id']);
        }
        
        if ($this->vars['group_code']) {
            $parent = R::findOne($this->table_group, '`alias` = ?', array($this->vars['group_code']));
            $res = R::find($this->table_user, 'group_id = ? ORDER BY last_name', array($parent->id));
            if ($parent)
                UAppBuilder::addBreadcrumb ($parent['title'], USite::getUrl()); 
        }
        
        return $this->returnResult(array(
            'form' => $res,
            'users' => $users
        ));
    }    

    public function newStudentAction($v = array())
    {
        $this->errors = array();
        if ($this->vars['in']) {            
            $r = R::findOne($this->table_group, "`alias` = ?", (array) $this->vars['in']);
            if ($r) {
                $v['group_id'] = $r['id'];
                UAppBuilder::addBreadcrumb($r['title'], USite::getModurl().'/group/'.$r['alias']);
            }
        } elseif ($this->vars['id']) {
            $_r = R::load($this->table_user, $this->vars['id']);
            $r = R::load($this->table_group, $_r['group_id']);
            if ($r) {
                $in = '/group/'.$r['alias'];
                UAppBuilder::addBreadcrumb($r['title'], USite::getModurl().$in);                
            }
        }
        if ($this->request->_post['a']) {
            $v = $this->request->_post;            
            
            $user = UUser::user()->doAction('admin', 'edit', array(array('password' => $v['password']), $v['id']));
            if ($user && empty($v['password']))
                USite::redirect(USite::getModurl().$in);            
        }
        $_list = R::findAll($this->table_group, 'ORDER BY title');
        $gList = array();
        foreach ($_list as $k => $j)
        {
            $gList[$k] = $j['title'];
        }
        return $this->returnResult(array(
                    'form' => $v,
                    'group_list' => $gList,
                    'user' => $user
        ));
    }    

    public function editStudentAction($id)
    {
        if (!$id)
            return;
        
        $v = R::load($this->table_user, $id);                
        if (UUser::getRootGroup($v['role']) !== 'student')
            return;
        
        return $this->newStudentAction($v);        
    }

}
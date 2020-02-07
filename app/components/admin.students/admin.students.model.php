<?php

class AdminStudentsModel extends UModel {

    private $table_group = 'u_univer_group';
    private $table_faculty = 'u_univer_faculty';
    private $table_speciality = 'u_univer_speciality';    
    private $table_user = 'u_user';
    private $table_roles = 'u_user_roles';

    public function groupAction()
    {
        if ($this->request->_post['del_all']) {
            $t = $this->vars['group_code'] ? $this->table_user : $this->table_group;
            foreach ($this->request->_post['i'] as $item)
            {
                $res = R::load($t, $item);
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

    public function newGroupAction($v = array())
    {
        $this->errors = array();
        if ($this->request->_post['a']) {
            $v = $this->request->_post;
            if (!$v['title'])
                $this->errors[] = 'Заполните название группы';
            if (!$v['speciality_id'])
                $this->errors[] = 'Укажите, к какой специальности относится группа';
            if (empty($this->errors)) {
                if ($v['id'])
                    $dataRow = R::load($this->table_group, $v['id']);
                else
                    $dataRow = R::dispense($this->table_group);
                $dataRow->title = $v['title'];
                $dataRow->speciality_id = $v['speciality_id'];
                $dataRow->alias = UAppBuilder::translit($v['title']);
                $this->checkUniq($dataRow->alias, $this->table_group);
                if (R::store($dataRow))
                    USite::redirect(USite::getModurl());
            }
        }
        $_list = R::findAll($this->table_speciality, 'ORDER BY title');
        $sList = array();
        $fList = R::findAll($this->table_faculty);
        foreach ($_list as $k => $j)
        {            
            $sList[$k] = $j['title'] . " [{$fList[ $j['faculty_id'] ]['title']}]";
        }
        return $this->returnResult(array(
                    'form' => $v,
                    'speciality_list' => $sList
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
            if ($v['id']) {
                $user = UUser::user()->edit($v, $v['id']);
                if ($user && empty($v['password']))
                    USite::redirect(USite::getModurl().$in);
            } else {
                $v['role'] = 'student';
                $user = UUser::user()->add($v);
            }
            $this->errors = UUser::$last_errors;            
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

    public function editGroupAction($id)
    {
        if (!$id)
            return;
        
        $v = R::load($this->table_group, $id);
        return $this->newGroupAction($v);
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

    public function deleteAction($type, $id)
    {
        if (!($type && $id))
            return;

        if ($type == 'group')
            $bean = R::load($this->table_group, $id);
        elseif ($type == 'student') {
            $bean = R::load($this->table_user, $id);
            $group = R::load($this->table_group, $bean['group_id']);
            $toback = '/' . $group['alias'];
        }

        if ($bean) {
            if ($type == 'student' && UUser::getRootGroup($bean['role']) !== 'student')
                USite::redirect(USite::getModurl());            
            R::trash($bean);
            USite::redirect(USite::getModurl() . '/group' . $toback);
        } else 
            USite::redirect(USite::getModurl());
    }
    
    protected function checkUniq(&$alias, $table)
    {        
        while ($res = R::findOne($table, '`alias` = ?', array($alias))) {
            $alias .= '-1';
        }
    }

}
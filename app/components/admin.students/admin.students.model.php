<?php

class AdminStudentsModel extends UModel {

    /*public function groupAction()
    {
        if ($this->request->_POST['del_all']) {
            $t = $this->vars['group_code'] ? TABLE_USER : TABLE_UNIVER_GROUP;
            foreach ($this->request->_POST['i'] as $item)
            {
                $res = R::load($t, $item);
                R::trash($res);
            }
            USite::redirect(USite::getUrl());
        } elseif ($this->request->_POST['newpass_all']) {
            $users = array();
            foreach ($this->request->_POST['i'] as $id)
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
        
        $res = R::findAll(TABLE_UNIVER_GROUP, 'ORDER BY title');
        foreach ($res as &$item)
        {
            $spec = R::load(TABLE_UNIVER_SPECIALITY, $item['speciality_id']);
            $item['speciality_name'] = $spec['title'];
            $item['students_count'] = R::count(TABLE_USER, "`group_id` = ?", (array)$item['id']);
        }
        
        if ($this->vars['group_code']) {
            $parent = R::findOne(TABLE_UNIVER_GROUP, '`alias` = ?', array($this->vars['group_code']));
            $res = R::find(TABLE_USER, 'group_id = ? ORDER BY last_name', array($parent->id));
            if ($parent)
                UAppBuilder::addBreadcrumb ($parent['title'], USite::getUrl()); 
        }
        
        return $this->returnResult(array(
            'form' => $res,
            'users' => $users
        ));
    }*/
    
    public function groupAction()
    {
        if ($this->request->_POST['del_all']) {            
            foreach ($this->request->_POST['i'] as $item)
            {
                $res = R::load(TABLE_UNIVER_GROUP, $item);
                R::trash($res);
            }
            USite::redirect(USite::getUrl());
        }         
        
        $res = R::findAll(TABLE_UNIVER_GROUP, 'ORDER BY title');
        foreach ($res as &$item)
        {
            $spec = R::load(TABLE_UNIVER_SPECIALITY, $item['speciality_id']);
            $item['speciality_name'] = $spec['title'];
            $item['students_count'] = R::count(TABLE_USER, "`group_id` = ?", (array)$item['id']);
        }
        
        if ($this->vars['group_code']) {            
            return $this->students($this->vars['group_code']);            
        }
        
        return $this->returnResult(array(
            'form' => $res,            
        ));
    }
    
    public function students($groupCode)
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
        
        $parent = R::findOne(TABLE_UNIVER_GROUP, '`alias` = ?', array($groupCode));
        $res = R::find(TABLE_USER, 'group_id = ? ORDER BY last_name', array($parent->id));
        if ($parent) {
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
        if ($this->request->_POST['a']) {
            $v = $this->request->_POST;
            if (!$v['title'])
                $this->errors[] = 'Заполните название группы';
            if (!$v['speciality_id'])
                $this->errors[] = 'Укажите, к какой специальности относится группа';
            if (empty($this->errors)) {
                if ($v['id'])
                    $dataRow = R::load(TABLE_UNIVER_GROUP, $v['id']);
                else
                    $dataRow = R::dispense(TABLE_UNIVER_GROUP);
                $dataRow->title = $v['title'];
                $dataRow->speciality_id = $v['speciality_id'];
                $dataRow->alias = UAppBuilder::translit($v['title']);
                $this->checkUniq($dataRow->alias, TABLE_UNIVER_GROUP);
                if (R::store($dataRow))
                    USite::redirect(USite::getModurl());
            }
        }
        $_list = R::findAll(TABLE_UNIVER_SPECIALITY, 'ORDER BY title');
        $sList = array();
        $fList = R::findAll(TABLE_UNIVER_FACULTY);
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
            $r = R::findOne(TABLE_UNIVER_GROUP, "`alias` = ?", (array) $this->vars['in']);
            if ($r) {
                $v['group_id'] = $r['id'];
                UAppBuilder::addBreadcrumb($r['title'], USite::getModurl().'/group/'.$r['alias']);
            }
        } elseif ($this->vars['id']) {
            $_r = R::load(TABLE_USER, $this->vars['id']);
            $r = R::load(TABLE_UNIVER_GROUP, $_r['group_id']);
            if ($r) {
                $in = '/group/'.$r['alias'];
                UAppBuilder::addBreadcrumb($r['title'], USite::getModurl().$in);                
            }
        }
        if ($this->request->_POST['a']) {
            $v = $this->request->_POST;            
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
        $_list = R::findAll(TABLE_UNIVER_GROUP, 'ORDER BY title');
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
        
        $v = R::load(TABLE_UNIVER_GROUP, $id);
        return $this->newGroupAction($v);
    }

    public function editStudentAction($id)
    {
        if (!$id)
            return;
        
        $v = R::load(TABLE_USER, $id);                
        if (UUser::getRootGroup($v['role']) !== 'student')
            return;
        
        return $this->newStudentAction($v);        
    }

    public function deleteAction($type, $id)
    {
        if (!($type && $id))
            return;

        if ($type == 'group')
            $bean = R::load(TABLE_UNIVER_GROUP, $id);
        elseif ($type == 'student') {
            $bean = R::load(TABLE_USER, $id);
            $group = R::load(TABLE_UNIVER_GROUP, $bean['group_id']);
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
<?php

namespace UTest\Components;

class AdminStudentsModel extends ComponentModel {
    
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
        
        return $this->returnResult($res);
    }
    
    public function studentAction($groupCode)
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
        if ($this->request->_POST['a']) {
            $this->errors = array();
            $v = $this->request->_POST;
            $required = array(
                'title' => 'Заполните название группы',
                'speciality_id' => 'Укажите, к какой специальности относится группа'
            );
            foreach ($required as $k => $message)
            {
                if (empty($v[$k])) {
                    $this->errors[] = $message;
                }
            }             
            if (empty($this->errors)) {
                if ($v['id']) {
                    $dataRow = R::load(TABLE_UNIVER_GROUP, $v['id']);
                } else {
                    $dataRow = R::dispense(TABLE_UNIVER_GROUP);
                }
                $dataRow->title = $v['title'];
                $dataRow->speciality_id = $v['speciality_id'];
                $dataRow->alias = UAppBuilder::translit($v['title']);
                UUtilities::checkUniq($dataRow->alias, TABLE_UNIVER_GROUP);
                if (R::store($dataRow)) {
                    USite::redirect(USite::getModurl());
                }
            }
        }
        $_list = R::findAll(TABLE_UNIVER_SPECIALITY, 'ORDER BY faculty_id, title');
        $sList = array();
        $fList = R::findAll(TABLE_UNIVER_FACULTY);
        foreach ($_list as $k => $j)
        {            
            $sList[$k] = "[{$fList[ $j['faculty_id'] ]['title']}] - " . $j['title'];
        }
        return $this->returnResult(array(
            'form' => $v,
            'speciality_list' => $sList
        ));
    }

    public function newStudentAction($v = array())
    {       
        var_dump($this->vars);die;
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
            $this->errors = array();
            $v = $this->request->_POST;            
            if ($v['id']) {
                $user = UUser::user()->edit($v, $v['id']);
                if ($user && empty($v['password'])) {
                    USite::redirect(USite::getModurl().$in);
                }
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
        $v = R::load(TABLE_UNIVER_GROUP, $id);        
        if (!$v['id']) {
            $this->errors = ERROR_ELEMENT_NOT_FOUND;
        }
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
        if (!($type && $id)) {
            return;
        }

        if ($type == 'group') {            
            $bean = R::load(TABLE_UNIVER_GROUP, $id);
        } elseif ($type == 'student') {
            $bean = R::load(TABLE_USER, $id);
            if (UUser::getRootGroup($bean['role']) !== 'student') {
                USite::redirect(USite::getModurl());            
            }
            $group = R::load(TABLE_UNIVER_GROUP, $bean['group_id']);
            $toback = $group['alias'];            
        }
            
        R::trash($bean);
        USite::redirect(USite::getModurl() . '/' . $toback);        
    }    

}
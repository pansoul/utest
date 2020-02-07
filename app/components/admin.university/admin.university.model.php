<?php

class AdminUniversityModel extends UModel {
    
    private $table_faculty = 'u_univer_faculty';
    private $table_speciality = 'u_univer_speciality';
    
    public function facultyAction()
    {   
        if ($this->request->_post['del_all']) {
            $t = $this->vars['faculty_code'] ? $this->table_speciality : $this->table_faculty;
            foreach ($this->request->_post['i'] as $item)
            {
                $res = R::load($t, $item);
                R::trash($res);
            }
            USite::redirect(USite::getUrl());
        }
        $res = R::findAll($this->table_faculty, 'ORDER BY title');   
        if ($this->vars['faculty_code']) {
            $parent = R::findOne($this->table_faculty, '`alias` = ?', array($this->vars['faculty_code']));
            $res = R::find($this->table_speciality, 'faculty_id = ? ORDER BY title', array($parent->id));       
            if ($parent)
                UAppBuilder::addBreadcrumb ($parent['title'], USite::getUrl());
        }
        return $this->returnResult($res);
    }    
    
    public function newFacultyAction($v = array())
    {        
        $this->errors = array();
        if ($this->request->_post['a']) {
            $v = $this->request->_post;
            if (!$v['title'])
                $this->errors[] = 'Заполните название факультета';
            if (empty($this->errors)) {
                if ($v['id'])
                    $dataRow = R::load($this->table_faculty, $v['id']);
                else
                    $dataRow = R::dispense($this->table_faculty);
                $dataRow->title = $v['title'];
                $dataRow->alias = UAppBuilder::translit($v['title']);  
                $this->checkUniq($dataRow->alias, $this->table_faculty);
                if (R::store($dataRow))
                    USite::redirect (USite::getModurl());
            }
        }        
        return $this->returnResult($v);
    }
    
    public function newSpecialityAction($v = array())
    {        
        if ($this->vars['in']) {
            $in = '/' . $this->vars['in'];
            $r = R::findOne($this->table_faculty, "`alias` = ?", (array)$this->vars['in']);
            if ($r) {
                $v['faculty_id'] = $r['id'];
                UAppBuilder::addBreadcrumb($r['title'], USite::getModurl().'/faculty/'.$r['alias']);
            }
        } elseif ($this->vars['id']) {
            $_r = R::load($this->table_speciality, $this->vars['id']);
            $r = R::load($this->table_faculty, $_r['faculty_id']);
            if ($r)
                UAppBuilder::addBreadcrumb($r['title'], USite::getModurl().'/faculty/'.$r['alias']);
        }
        if ($this->request->_post['a']) {
            $v = $this->request->_post;
            if (!$v['title'])
                $this->errors[] = 'Заполните название специальности';
            if (!$v['code'])
                $this->errors[] = 'Укажите код специальности';
            if (empty($this->errors)) {
                if ($v['id']) {
                    $dataRow = R::load($this->table_speciality, $v['id']);
                    $r = R::load($this->table_faculty, $dataRow['faculty_id']);
                    $in = '/' . $r['alias'];
                } else
                    $dataRow = R::dispense($this->table_speciality);
                $dataRow->title = $v['title'];
                $dataRow->faculty_id = $v['faculty_id'];
                $dataRow->code = $v['code'];                
                if (R::store($dataRow)) {                                        
                    USite::redirect (USite::getModurl().$in);
                }
            }
        }                
        $_list = R::findAll($this->table_faculty, 'ORDER BY title');    
        $fList = array();
        foreach ($_list as $k => $j)
        {
            $fList[$k] = $j['title'];
        }
        return $this->returnResult(array(
            'form' => $v,
            'faculty_list' => $fList
        ));
    }
    
    public function editFacultyAction($id)
    {
        if (!$id)
            return;
        $v = R::load($this->table_faculty, $id);
        return $this->newFacultyAction($v);
    }
    
    public function editSpecialityAction($id)
    {
        if (!$id)
            return;
        $v = R::load($this->table_speciality, $id);
        return $this->newSpecialityAction($v);
    }
    
    public function deleteAction($type, $id)
    {
        if (!($type && $id))
            return;
        
        if ($type == 'faculty')
            $bean = R::load($this->table_faculty, $id);
        elseif ($type == 'speciality') {
            $bean = R::load($this->table_speciality, $id);
            $faculty = R::load($this->table_faculty, $bean['faculty_id']);            
            $toback = '/' . $faculty['alias'];
        }
        
        if ($bean) {
            R::trash($bean);
            USite::redirect(USite::getModurl().'/faculty'.$toback);
        }
    }
    
    protected function checkUniq(&$alias, $table)
    {        
        while ($res = R::findOne($table, '`alias` = ?', array($alias))) {
            $alias .= '-1';
        }
    }
}
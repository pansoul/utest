<?php

class AdminUniversityModel extends ComponentModel {
    
    public function facultyAction()
    {   
        if ($this->request->_POST['del_all']) {            
            foreach ($this->request->_POST['i'] as $item)
            {
                $res = R::load(TABLE_UNIVER_FACULTY, $item);
                R::trash($res);
            }
            USite::redirect(USite::getUrl());
        }
        $res = R::findAll(TABLE_UNIVER_FACULTY, 'ORDER BY title');           
        return $this->returnResult($res);
    } 
    
    public function newFacultyAction($v = array())
    {   
        if ($this->request->_POST['a']) {
            $this->errors = array();
            $v = $this->request->_POST;
            if (!$v['title']) {
                $this->errors[] = 'Заполните название факультета';
            }
            if (empty($this->errors)) {
                $dataRow = $v['id'] 
                    ? R::load(TABLE_UNIVER_FACULTY, $v['id'])
                    : R::dispense(TABLE_UNIVER_FACULTY);                
                $dataRow->title = $v['title'];
                $dataRow->alias = UAppBuilder::translit($v['title']);  
                UUtilities::checkUniq($dataRow->alias, TABLE_UNIVER_FACULTY);
                if (R::store($dataRow)) {
                    USite::redirect (USite::getModurl());
                }
            }
        }        
        return $this->returnResult($v);
    }
    
    public function specialityAction($facultyCode)
    {
        if ($this->request->_POST['del_all'] && !$this->watcherStatus) {            
            foreach ($this->request->_POST['i'] as $item)
            {
                $res = R::load(TABLE_UNIVER_SPECIALITY, $item);
                R::trash($res);
            }
            USite::redirect(USite::getUrl());
        }
        $parent = R::findOne(TABLE_UNIVER_FACULTY, '`alias` = ?', array($facultyCode));
        $res = R::find(TABLE_UNIVER_SPECIALITY, 'faculty_id = ? ORDER BY title', array($parent->id));               
        if ($parent) {
            UAppBuilder::addBreadcrumb ($parent['title'], USite::getModurl() . '/' . $this->vars['faculty_code']);
        } else {
            $this->setErrors('Факультет не найден', ERROR_ELEMENT_NOT_FOUND);            
        }       
        return $this->returnResult($res);
    }
    
    public function newSpecialityAction($v = array())
    {   
        $this->doAction('speciality', $this->vars['faculty_code'], true);          
        if ($this->errorsCode == ERROR_ELEMENT_NOT_FOUND) {
            return $this->returnResult();
        }
        
        $in = '/' . $this->vars['faculty_code'];
        $r = R::findOne(TABLE_UNIVER_FACULTY, "`alias` = ?", (array)$this->vars['faculty_code']);
        if ($r) {
            $v['faculty_id'] = $r['id'];
            UAppBuilder::addBreadcrumb($r['title'], USite::getModurl().'/'.$r['alias']);
        }        
        
        if ($this->request->_POST['a']) {                        
            $this->errors = array();
            $v = $this->request->_POST;            
            $required = array(
                'title' => 'Заполните название специальности',
                'code' => 'Укажите код специальности'
            );
            foreach ($required as $k => $message)
            {
                if (empty($v[$k])) {
                    $this->errors[] = $message;
                }
            }            
            if (empty($this->errors)) {                
                if ($v['id']) {
                    $dataRow = R::load(TABLE_UNIVER_SPECIALITY, $v['id']);
                    $r = R::load(TABLE_UNIVER_FACULTY, $dataRow['faculty_id']);
                    $in = '/' . $r['alias'];
                } else {
                    $dataRow = R::dispense(TABLE_UNIVER_SPECIALITY);
                }
                $dataRow->title = $v['title'];
                $dataRow->faculty_id = $v['faculty_id'];
                $dataRow->code = $v['code'];                
                if (R::store($dataRow)) {                                        
                    USite::redirect (USite::getModurl().$in);
                }
            }
        }
        
        $_list = R::findAll(TABLE_UNIVER_FACULTY, 'ORDER BY title');    
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
        $v = R::load(TABLE_UNIVER_FACULTY, $id);        
        if (!$v['id']) {
            $this->setErrors('Факультет не найден', ERROR_ELEMENT_NOT_FOUND);            
        }
        return $this->newFacultyAction($v);
    }
    
    public function editSpecialityAction($id)
    {
        $v = R::load(TABLE_UNIVER_SPECIALITY, $id);
        if (!$v['id']) {
            $this->setErrors('Специальность не найдена', ERROR_ELEMENT_NOT_FOUND);            
        }
        return $this->newSpecialityAction($v);
    }
    
    public function deleteAction($type, $id)
    {
        if (!($type && $id)) {
            return;
        }
        
        if ($type == 'faculty') {
            $bean = R::load(TABLE_UNIVER_FACULTY, $id);
        } elseif ($type == 'speciality') {
            $bean = R::load(TABLE_UNIVER_SPECIALITY, $id);
            $faculty = R::load(TABLE_UNIVER_FACULTY, $bean['faculty_id']);            
            $toback = $faculty['alias'];
        }        
        
        R::trash($bean);
        USite::redirect(USite::getModurl() . '/' . $toback);        
    }
}
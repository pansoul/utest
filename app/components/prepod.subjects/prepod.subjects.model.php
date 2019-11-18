<?php

class PrepodSubjectsModel extends ComponentModel {
    
    private $table_subject = 'u_prepod_subject';

    public function subjectAction()
    {
        if ($this->request->_POST['del_all']) {            
            foreach ($this->request->_POST['i'] as $item)
            {
                $res = R::load($this->table_subject, $item);
                R::trash($res);
            }
            USite::redirect(USite::getUrl());
        } 
        
        $res = R::find($this->table_subject, 'user_id = ? ORDER BY title', array(UUser::user()->getUID()));        
        
        return $this->returnResult($res);
    }

    public function newSubjectAction($v = array())
    {
        $this->errors = array();
        if ($this->request->_POST['a']) {
            $v = $this->request->_POST;
            if (!$v['title'])
                $this->errors[] = 'Заполните название предмета';
            if (empty($this->errors)) {
                if ($v['id'])
                    $dataRow = R::load($this->table_subject, $v['id']);
                else {
                    $dataRow = R::dispense($this->table_subject);
                    $dataRow->user_id = UUser::user()->getUID();
                }
                $dataRow->title = $v['title'];
                $dataRow->alias = UAppBuilder::translit($v['title']);
                $this->checkUniq($dataRow->alias, $this->table_subject);
                if (R::store($dataRow))
                    USite::redirect(USite::getModurl());
            }
        }
        return $this->returnResult($v);
    }

    public function editAction($id)
    {
        if (!$id)
            return;
        
        $v = R::findOne($this->table_subject, 'user_id = :uid AND id = :id', array(
            ':uid' => UUser::user()->getUID(),
            ':id' => $id
        ));
        return $this->newSubjectAction($v);
    }

    public function deleteAction($id)
    {
        if (!$id)
            return;
        
        $bean = R::findOne($this->table_subject, 'user_id = :uid AND id = :id', array(
            ':uid' => UUser::user()->getUID(),
            ':id' => $id
        ));
        if ($bean) {
            R::trash($bean);
            USite::redirect(USite::getModurl());
        } else 
            USite::redirect(USite::getModurl());
    }
    
    protected function checkUniq(&$alias, $table)
    {        
        while ($res = R::findOne($table, '`alias` = :alias AND user_id = :uid ', array(
            ':alias' => $alias, 
            ':uid' => UUser::user()->getUID()
        ))) {
            $alias .= '-1';
        }
    }

}
<?php

class StudentMaterialsModel extends ComponentModel {

    private $table_subject = 'u_prepod_subject';
    private $table_material = 'u_prepod_material';
    private $table_user = 'u_user';
    private $table_student_material = 'u_student_material';

    public function myAction()
    {
        $g = UUser::user()->getFields(array('group_id'));
        
        $sql = "
            SELECT DISTINCT m.*, u.last_name as prepod_last_name, u.name as prepod_name, u.surname as prepod_surname
            FROM {$this->table_subject} AS m 
            LEFT JOIN {$this->table_student_material} AS s 
                ON (s.subject_id = m.id)
            LEFT JOIN {$this->table_user} AS u 
                ON (m.user_id = u.id)
            WHERE
                s.group_id = {$g['group_id']}
                AND s.is_hidden = 0
            ORDER BY m.title
        ";
        $records = R::getAll($sql);
        $res = R::convertToBeans($this->table_subject, $records);
        
        foreach ($res as &$item)
        {
            $item['material_count'] = R::count($this->table_student_material, 'subject_id = :sid AND group_id = :gid ', array(
                        ':sid' => $item['id'],
                        ':gid' => $g['group_id']
            ));
        }

        if ($this->vars['subject_code']) {
            $parent = R::findOne($this->table_subject, '`alias` = ? ', array($this->vars['subject_code']));
            
            if ($parent) {
                UAppBuilder::addBreadcrumb($parent['title'], USite::getUrl());
                
                $sql = "
                    SELECT s.*, m.filename, m.size, m.extension
                    FROM {$this->table_student_material} AS s 
                    LEFT JOIN {$this->table_material} AS m 
                        ON (m.id = s.material_id)
                    WHERE
                        s.group_id = {$g['group_id']}
                        AND s.subject_id = {$parent['id']}                    
                    ORDER BY s.date DESC
                ";      
                $records = R::getAll($sql);
                $res = R::convertToBeans($this->table_student_material, $records);
                
                if ($this->request->_GET['download']) 
                    $this->fileDownload($this->request->_GET['download']);
            }
        }

        return $this->returnResult($res);
    }
    
    function fileDownload($docId)
    {
        $docId = intval($docId);
        $g = UUser::user()->getFields(array('group_id'));
        $parent = R::findOne($this->table_subject, '`alias` = ? ', array($this->vars['subject_code']));
        
        $sql = "
            SELECT m.*
            FROM {$this->table_material} AS m 
            LEFT JOIN {$this->table_student_material} AS s 
                ON (m.id = s.material_id)
            WHERE
                m.id = $docId
                AND s.group_id = {$g['group_id']}
                AND s.subject_id = {$parent['id']}
                AND s.is_hidden = 0
        ";                        
        $records = R::getAll($sql);
        $res = R::convertToBeans($this->table_material, $records);
        $result = reset($res);

        if (!$result) {
            return false;
        }

        $file = ROOT . $result['filepath'];

        if (file_exists($file)) {
            // сбрасываем буфер вывода PHP, чтобы избежать переполнения памяти выделенной под скрипт
            // если этого не сделать файл будет читаться в память полностью!
            if (ob_GET_level()) {
                ob_end_clean();
            }

            $f = str_replace(' ', '_', $result['filename']) . '.' . $result['extension'];
            if (stristr($_SERVER['HTTP_USER_AGENT'], 'MSIE 8.0') 
                || stristr($_SERVER['HTTP_USER_AGENT'], 'MSIE 7.0')) {
                
                $f = str_replace('+', '_', urlencode($result['filename'])) . '.' . $result['extension'];
            }

            // заставляем браузер показать окно сохранения файла
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . $f);
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));

            // читаем файл и отправляем его пользователю
            readfile($file);
            exit;
        }
    }

}
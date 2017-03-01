<?php

class PrepodMaterialsModel extends UModel {

    private $dir = '/uploads/materials';
    private $table_subject = 'u_prepod_subject';
    private $table_material = 'u_prepod_material';
    private $table_student_material = 'u_student_material';
    private $table_group = 'u_univer_group';

    public function myAction()
    {
        if ($this->request->_POST['del_all']) {
            foreach ($this->request->_POST['i'] as $item)
            {
                if (!$item)
                    continue;

                $res = R::findOne($this->table_material, 'id = :id AND user_id = :uid ', array(
                            ':id' => $item,
                            ':uid' => UUser::user()->getUID()
                ));

                @unlink(ROOT . $res['filepath']);
                R::trash($res);
            }
            USite::redirect(USite::getUrl());
        }

        $res = R::find($this->table_subject, 'user_id = ? ORDER BY title', array(UUser::user()->getUID()));
        foreach ($res as &$item)
        {
            $item['material_count'] = R::count($this->table_material, 'subject_id = :sid AND user_id = :uid ', array(
                        ':sid' => $item['id'],
                        ':uid' => UUser::user()->getUID()
            ));
        }

        if ($this->vars['subject_code']) {
            $parent = R::findOne($this->table_subject, '`alias` = :alias AND user_id = :uid ', array(
                        ':alias' => $this->vars['subject_code'],
                        ':uid' => UUser::user()->getUID()
            ));
            $res = R::find($this->table_material, 'subject_id = ? ORDER BY date', array($parent->id));
            if ($parent) {
                UAppBuilder::addBreadcrumb($parent['title'], USite::getUrl());
                
                if ($this->request->_GET['download']) 
                    $this->fileDownload($this->request->_GET['download']);
            }
        }

        return $this->returnResult($res);
    }

    public function forAction()
    {
        // если есть данные о выбранной группе
        if ($this->vars['group_code']) {
            $gparent = R::findOne($this->table_group, '`alias` = ?', array($this->vars['group_code']));

            // группа найдена
            if ($gparent) {
                UAppBuilder::addBreadcrumb($gparent['title'], USite::getModurl() . '/for/' . $gparent['alias']);

                // если есть данные о выбранном предмете
                if ($this->vars['subject_code']) {
                    $sparent = R::findOne($this->table_subject, '`alias` = :alias AND user_id = :uid ', array(
                                ':alias' => $this->vars['subject_code'],
                                ':uid' => UUser::user()->getUID()
                    ));

                    // предмет найден
                    if ($sparent) {
                        UAppBuilder::addBreadcrumb($sparent['title'], USite::getUrl());
                        
                         if ($this->request->_POST['del_all']) {
                            foreach ($this->request->_POST['i'] as $item)
                            {
                                if (!$item)
                                    continue;

                                $sql = "
                                    SELECT s.*
                                    FROM {$this->table_student_material} AS s 
                                    LEFT JOIN {$this->table_material} AS p 
                                        ON (s.subject_id = p.subject_id)
                                    WHERE
                                        s.id = {$item}
                                        AND s.comment IS NOT NULL
                                        AND p.user_id = " . UUser::user()->getUID() . "                                
                                ";
                                $record = R::getAll($sql);
                                $comment = R::convertToBeans($this->table_student_material, $record);
                                R::trash(reset($comment));
                            }
                            USite::redirect(USite::getUrl());
                        }
                        
                        $sql = "
                            SELECT s.*, p.filename, p.size, p.extension
                            FROM {$this->table_student_material} AS s 
                            LEFT JOIN {$this->table_material} AS p 
                                ON (s.material_id = p.id)
                            WHERE
                                s.group_id = {$gparent['id']}
                                AND s.is_hidden = 0
                                AND s.comment IS NULL
                                AND p.subject_id = {$sparent['id']}
                                AND p.user_id = " . UUser::user()->getUID() . "                                
                            ORDER BY p.filename
                        ";
                        $res = R::getAll($sql);
                        /* $sql = "
                          SELECT s.*
                          FROM :s AS s
                          LEFT JOIN `:p` AS p
                          ON (s.material_id = p.id)
                          WHERE
                          s.group_id = :gid
                          AND p.subject_id = :sid
                          AND p.user_id = :uid
                          ";
                          $res = R::getAll($sql, array(
                          ':s' => 'test',
                          ':p' => $this->table_material,
                          ':gid' => $gparent['id'],
                          ':sid' => $sparent['id'],
                          ':uid' => UUser::user()->getUID()
                          )); */
                        $records = R::getAll($sql);
                        $res = R::convertToBeans($this->table_student_material, $records);
                        
                        $sql = "
                            SELECT DISTINCT s.*
                            FROM {$this->table_student_material} AS s 
                            LEFT JOIN {$this->table_material} AS p 
                                ON (s.subject_id = p.subject_id)
                            WHERE
                                s.group_id = {$gparent['id']}
                                AND s.comment IS NOT NULL
                                AND s.subject_id = {$sparent['id']}
                                AND p.user_id = " . UUser::user()->getUID() . "                                
                            ORDER BY s.date DESC
                        ";
                        $records = R::getAll($sql);
                        $comments = R::convertToBeans($this->table_student_material, $records);
                    }
                }
                else
                    $res = R::find($this->table_subject, 'user_id = ? ORDER BY title', array(UUser::user()->getUID()));
            }
        }
        // Выбор групп
        else {
            $res = R::findAll($this->table_group, 'ORDER BY title');
        }
        return $this->returnResult(array(
            'form' => $res,
            'comments' => $comments
        ));
    }

    public function newMyAction($v = array())
    {
        $this->errors = array();
        if ($this->vars['in']) {
            $r = R::findOne($this->table_subject, 'user_id = :uid AND `alias` = :alias', array(
                        ':uid' => UUser::user()->getUID(),
                        ':alias' => $this->vars['in']
            ));
            if ($r) {
                $v['subject_id'] = $r['id'];
                $in = '/my/' . $r['alias'];
                UAppBuilder::addBreadcrumb($r['title'], USite::getModurl() . '/my/' . $r['alias']);
            }
        } elseif ($this->vars['id']) {
            $_r = R::load($this->table_material, $this->vars['id']);
            $r = R::load($this->table_subject, $_r['subject_id']);
            if ($r) {
                $in = '/my/' . $r['alias'];
                UAppBuilder::addBreadcrumb($r['title'], USite::getModurl() . $in);
            }
        }
        if ($this->request->_POST['a']) {
            $v = $this->request->_POST;

            if (!$v['filename'])
                $this->errors[] = 'Укажите название документа';
            if (!$v['id'] && empty($_FILES['material']['name']))
                $this->errors[] = 'Выберите файл';

            if (empty($this->errors)) {
                if ($v['id'])
                    $dataRow = R::findOne($this->table_material, 'user_id = :uid AND id = :id', array(
                                ':uid' => UUser::user()->getUID(),
                                ':id' => $v['id']
                    ));
                else {
                    $dataRow = R::dispense($this->table_material);
                    $dataRow->user_id = UUser::user()->getUID();
                }

                $dataRow->filename = $v['filename'];
                $dataRow->subject_id = $v['subject_id'];
                // file upload
                $pathInfo = pathinfo($_FILES['material']['name']);
                $fileName = time();
                $fileExt = $pathInfo['extension'];

                $dirMaterials = ROOT . $this->dir;
                if (!is_dir($dirMaterials))
                    mkdir($dirMaterials, 0755);

                $dirMatPrepod = $dirMaterials . '/p-' . UUser::user()->getUID();
                if (!is_dir($dirMatPrepod))
                    mkdir($dirMatPrepod, 0755);

                $filePath = $this->dir . '/p-' . UUser::user()->getUID() . '/' . $fileName . '.' . $fileExt;
                $rootFilePath = ROOT . $filePath;
                if (!empty($_FILES['material']['name'])) {
                    if (is_uploaded_file($_FILES['material']['tmp_name'])) {
                        if (move_uploaded_file($_FILES['material']['tmp_name'], $rootFilePath)) {
                            $dataRow->date = UAppBuilder::getDateTime();
                            $dataRow->filename_original = $pathInfo['filename'];
                            $dataRow->filepath = $filePath;
                            $dataRow->size = $_FILES['material']['size'];
                            $dataRow->extension = $fileExt;
                        }
                        else
                            $this->errors[] = 'Ошибка при перемещении загруженного файла';
                    }
                    else
                        $this->errors[] = 'Ошибка при загрузке файла';
                }

                if (empty($this->errors)) {
                    if (R::store($dataRow))
                        USite::redirect(USite::getModurl() . $in);
                }
            }
        }
        $_list = R::find($this->table_subject, 'user_id = ?', array(UUser::user()->getUID()));
        $sList = array();
        foreach ($_list as $k => $j)
        {
            $sList[$k] = $j['title'];
        }
        return $this->returnResult(array(
                    'form' => $v,
                    'subject_list' => $sList
        ));
    }

    public function newForAction()
    {
        if ($this->vars['group_code']) {
            $gparent = R::findOne($this->table_group, '`alias` = ?', array($this->vars['group_code']));

            // группа найдена
            if ($gparent) {
                UAppBuilder::addBreadcrumb($gparent['title'], USite::getModurl() . '/for/' . $gparent['alias']);

                // если есть данные о выбранном предмете
                if ($this->vars['subject_code']) {
                    $sparent = R::findOne($this->table_subject, '`alias` = :alias AND user_id = :uid ', array(
                                ':alias' => $this->vars['subject_code'],
                                ':uid' => UUser::user()->getUID()
                    ));

                    // предмет найден
                    if ($sparent) {
                        $in = '/for/' . $gparent['alias'] . '/' . $sparent['alias'];
                        UAppBuilder::addBreadcrumb($sparent['title'], USite::getModurl() . $in);

                        // Находим спсисок тех документов, что выложенны в данный момент для группы
                        $sql = "
                            SELECT s.*, p.filename, p.size, p.extension
                            FROM {$this->table_student_material} AS s 
                            LEFT JOIN {$this->table_material} AS p 
                                ON (s.material_id = p.id)
                            WHERE
                                s.group_id = {$gparent['id']}
                                AND s.is_hidden = 0
                                AND s.comment IS NULL
                                AND p.subject_id = {$sparent['id']}
                                AND p.user_id = " . UUser::user()->getUID() . "                                
                            ORDER BY p.filename
                        ";
                        $records = R::getAll($sql);
                        $_list = R::convertToBeans($this->table_student_material, $records);
                        $activeList = array();
                        foreach ($_list as $k => $j)
                        {
                            $activeList[] = $j['material_id'];
                        }

                        // Находим массив всех доступных документов по выбранному предмету
                        $_list = R::find($this->table_material, 'user_id = :uid AND subject_id = :sid', array(
                                    ':uid' => UUser::user()->getUID(),
                                    ':sid' => $sparent['id']
                        ));
                        $sList = array();
                        foreach ($_list as $k => $j)
                        {
                            $sList[$k] = $j['filename'] . '.' . $j['extension'] . ' (' . UAppBuilder::bytesToSize($j['size']) . ')';
                        }

                        // Запрос на сохранение
                        if ($this->request->_POST['a']) {
                            $curList = $this->request->_POST['materials'];
                            $diffList = $curList === null ? $sList : array_diff($activeList, $curList);  
                            foreach ($curList as $id)                                                        
                            {                                
                                $r = R::findOrDispense($this->table_student_material, 'material_id = :mid AND group_id = :gid', array(
                                    ':mid' => $id,
                                    ':gid' => $gparent['id']
                                ));  
                                $dataRow = reset($r);                                
                                if (!$dataRow->id) {
                                    $dataRow->group_id = $gparent['id'];
                                    $dataRow->subject_id = $sparent['id'];
                                    $dataRow->material_id = $id;
                                    $dataRow->date = UAppBuilder::getDateTime();
                                    $dataRow->is_hidden = 0;
                                } elseif ($dataRow->is_hidden) {
                                    $dataRow->date = UAppBuilder::getDateTime();
                                    $dataRow->is_hidden = 0;
                                } else
                                    $dataRow->is_hidden = 0;
                                
                                R::store($dataRow);
                            }
                            foreach ($diffList as $k => $j)                            
                            {
                                $id = $curList === null ? $k : $j;
                                $dataRow = R::findOne($this->table_student_material, 'material_id = :mid AND group_id = :gid', array(
                                    ':mid' => $id,
                                    ':gid' => $gparent['id']
                                ));
                                $dataRow->is_hidden = 1;
                                R::store($dataRow);
                            }
                            USite::redirect(USite::getModurl() . $in);
                        }
                    }
                }
            }
        }

        return $this->returnResult(array(
            'active_list' => $activeList,
            'all_list' => $sList
        ));        
    }
    
    public function newCommentAction($v = array())
    {
        $this->errors = array();
        if ($this->vars['id']) {
            $res = $v;
            $this->vars['group_code'] = $v['group_code'];
            $this->vars['subject_code'] = $v['subject_code'];
        }         
        if ($this->vars['group_code']) {
            $gparent = R::findOne($this->table_group, '`alias` = ?', array($this->vars['group_code']));

            // группа найдена
            if ($gparent) {
                UAppBuilder::addBreadcrumb($gparent['title'], USite::getModurl() . '/for/' . $gparent['alias']);

                // если есть данные о выбранном предмете
                if ($this->vars['subject_code']) {
                    $sparent = R::findOne($this->table_subject, '`alias` = :alias AND user_id = :uid ', array(
                                ':alias' => $this->vars['subject_code'],
                                ':uid' => UUser::user()->getUID()
                    ));

                    // предмет найден
                    if ($sparent) {
                        $in = '/for/' . $gparent['alias'] . '/' . $sparent['alias'];
                        UAppBuilder::addBreadcrumb($sparent['title'], USite::getModurl() . $in);
                        
                        // Запрос на изменение
                        if ($this->request->_POST['a']) {                            
                            $v = $this->request->_POST;
                            
                            if (!$v['comment'])
                                $this->errors[] = "Заполните текст комментария";
                            
                            if (empty($this->errors)) {
                                $r = R::findOrDispense($this->table_student_material, 'id = ?', array($v['id']));                    
                                $dataRow = reset($r);
                                $dataRow->comment = $v['comment'];
                                if (!$dataRow->id) {
                                    $dataRow->date = UAppBuilder::getDateTime();
                                    $dataRow->group_id = $gparent['id'];
                                    $dataRow->subject_id = $sparent['id'];
                                }
                                if (R::store($dataRow))
                                    USite::redirect(USite::getModurl() . $in);
                            }
                        }                         
                    }
                }
            }
        }
        return $this->returnResult($res);        
    }

    public function editMyAction($id)
    {
        if (!$id)
            return;

        $v = R::findOne($this->table_material, 'id = :id AND user_id = :uid ', array(
                    ':id' => $id,
                    ':uid' => UUser::user()->getUID()
        ));
        return $this->newMyAction($v);
    }

    public function editCommentAction($id)
    {
        if (!$id)
            return;

        $sql = "
            SELECT s.*, g.alias as group_code, j.alias as subject_code
            FROM {$this->table_student_material} AS s 
            LEFT JOIN {$this->table_material} AS p 
                ON (s.subject_id = p.subject_id)
            LEFT JOIN {$this->table_group} AS g 
                ON (g.id = s.group_id)
            LEFT JOIN {$this->table_subject} AS j 
                ON (j.id = s.subject_id)
            WHERE
                s.id = {$id}
                AND s.comment IS NOT NULL
                AND p.user_id = " . UUser::user()->getUID() . "
        ";
        $record = R::getAll($sql);
        $comment = R::convertToBeans($this->table_student_material, $record);
        $v = reset($comment);        

        return $this->newCommentAction($v);
    }

    public function deleteAction($type, $id)
    {
        if (!($type && $id))
            return;

        if ($type == 'my') {
            $bean = R::findOne($this->table_material, 'id = :id AND user_id = :uid ', array(
                        ':id' => $id,
                        ':uid' => UUser::user()->getUID()
            ));
            $subject = R::load($this->table_subject, $bean['subject_id']);
            $toback = '/my/' . $subject['alias'];
        } elseif ($type == 'comment') {
            $sql = "
                SELECT s.*, g.alias as group_code, j.alias as subject_code
                FROM {$this->table_student_material} AS s 
                LEFT JOIN {$this->table_material} AS p 
                    ON (s.subject_id = p.subject_id)
                LEFT JOIN {$this->table_group} AS g 
                    ON (g.id = s.group_id)
                LEFT JOIN {$this->table_subject} AS j 
                    ON (j.id = s.subject_id)
                WHERE
                    s.id = {$id}
                    AND s.comment IS NOT NULL
                    AND p.user_id = " . UUser::user()->getUID() . "
            ";
            $record = R::getAll($sql);
            $comment = R::convertToBeans($this->table_student_material, $record);
            $bean = reset($comment);

            $toback = '/for/' . $bean['group_code'] . '/' . $bean['subject_code'];
        }

        if ($bean) {
            @unlink(ROOT . $bean['filepath']);
            R::trash($bean);
            USite::redirect(USite::getModurl() . $toback);
        }
        else
            USite::redirect(USite::getModurl());
    }
    
    function fileDownload($docId)
    {
        $docId = intval($docId);
        $result = R::findOne($this->table_material, 'id = :id AND user_id = :uid ', array(
                    ':id' => $docId,
                    ':uid' => UUser::user()->getUID()
        ));

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
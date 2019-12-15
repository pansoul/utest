<?php

namespace UTest\Components;

use UTest\Kernel\DB;
use UTest\Kernel\User\User;
use UTest\Kernel\Site;
use UTest\Kernel\Utilities;
use \Verot\Upload\Upload;

class PrepodMaterialsModel extends \UTest\Kernel\Component\Model
{
    public function myAction()
    {
        $res = DB::table(TABLE_PREPOD_SUBJECT)
            ->select(
                TABLE_PREPOD_SUBJECT.'.*',
                DB::raw('count('.TABLE_PREPOD_MATERIAL.'.id) as material_count')
            )
            ->leftJoin(TABLE_PREPOD_MATERIAL, TABLE_PREPOD_MATERIAL.'.subject_id', '=', TABLE_PREPOD_SUBJECT.'.id')
            ->where(TABLE_PREPOD_SUBJECT.'.user_id', '=', User::user()->getUID())
            ->groupBy(TABLE_PREPOD_SUBJECT.'.id')
            ->orderBy(TABLE_PREPOD_SUBJECT.'.title')
            ->get();

        $this->setData($res);
    }

    public function myMaterialAction($subjectCode)
    {
        if ($this->isActionRequest('del_all')) {
            foreach ($this->_POST['i'] as $id) {
                if (!$id) {
                    continue;
                }

                $res = DB::table(TABLE_PREPOD_MATERIAL)
                    ->where('id', '=', $id)
                    ->where('user_id', '=', User::user()->getUID())
                    ->first();

                if ($res) {
                    @unlink(ROOT . $res['filepath']);
                    DB::table(TABLE_PREPOD_MATERIAL)->delete($id);
                }
            }
            Site::redirect(Site::getUrl());
        }

        $parent = DB::table(TABLE_PREPOD_SUBJECT)
            ->where('alias', '=', $subjectCode)
            ->where('user_id', '=', User::user()->getUID())
            ->first();
        $res = DB::table(TABLE_PREPOD_MATERIAL)
            ->where('subject_id', '=', $parent['id'])
            ->where('user_id', '=', User::user()->getUID())
            ->orderBy('date')
            ->get();

        if (!$parent) {
            $this->setErrors('Предмет не найден', ERROR_ELEMENT_NOT_FOUND);
        }

        $this->setData($res);
        return $parent;
    }

    public function myNewAction($v = array())
    {
        $subject = $this->myMaterialAction($this->vars['subject_code']);
        if ($this->hasErrors(ERROR_ELEMENT_NOT_FOUND)) {
            $this->setData(null);
            return;
        }

        $subjectList = DB::table(TABLE_PREPOD_SUBJECT)
            ->where('user_id', '=', User::user()->getUID())
            ->orderBy('title')
            ->get()
            ->toArray();
        $subjectList = array_reduce($subjectList, function($acc, $item){
            $acc[$item['id']] = $item['title'];
            return $acc;
        }, []);

        if ($this->isActionRequest()) {
            $this->clearErrors();
            $v = $this->_POST;
            $required = array(
                'filename' => 'Укажите название документа',
                'subject_id' => 'Выберите предмет'
            );

            foreach ($required as $k => $message) {
                if (empty($v[$k])) {
                    $this->setErrors($message);
                }
            }

            if (!$v['id'] && empty($_FILES['material']['name'])) {
                $this->setErrors('Выберите файл');
            }

            if (!$this->hasErrors()) {
                $dataRow = [
                    'filename' => $v['filename'],
                    'subject_id' => isset($subjectList[$v['subject_id']]) ? $v['subject_id'] : $subject['id']
                ];
                if (!$v['id']) {
                    $dataRow['date'] = Utilities::getDateTime();
                    $dataRow['user_id'] = User::user()->getUID();
                }

                $upload = new Upload($_FILES['material']);
                if ($upload->uploaded) {
                    $upload->file_new_name_body = md5($upload->file_src_name_body . microtime(true));
                    $upload->process(Utilities::getUserUploadedDir());
                    if (!$upload->processed) {
                        $this->setErrors($upload->error);
                    } else {
                        $dataRow['extension'] = $upload->file_src_name_ext;
                        $dataRow['size'] = $upload->file_src_size;
                        $dataRow['filename_original'] = $upload->file_src_name_body;
                        $dataRow['filepath'] = Utilities::getUserUploadedDir(true) . '/' . $upload->file_dst_name;
                        $upload->clean();
                    }
                }

                // @todo прочекать у всех компонентов верные атрибуты для обновления/создания элемента
                if (DB::table(TABLE_PREPOD_MATERIAL)->updateOrInsert(['id' => $v['id'], 'user_id' => User::user()->getUID()], $dataRow)) {
                    Site::redirect(Site::getModurl() . '/my/' . $this->vars['subject_code']);
                } else {
                    @unlink($upload->file_dst_pathname);
                }
            }
        } else {
            $v['subject_id'] = $subject['id'];
        }

        $this->setData([
            'form' => $v,
            'subject_list' => $subjectList
        ]);
    }

    public function myEditAction($id)
    {
        $v = DB::table(TABLE_PREPOD_MATERIAL)
            ->where('user_id', '=', User::user()->getUID())
            ->where('id', '=', $id)
            ->first();
        if (!$v['id']) {
            $this->setErrors('Материал не найдена', ERROR_ELEMENT_NOT_FOUND);
        }
        return $this->myNewAction($v);
    }

    public function forAction()
    {
        $res = DB::table(TABLE_UNIVER_GROUP)
            ->select(
                TABLE_UNIVER_GROUP.'.*',
                TABLE_UNIVER_SPECIALITY.'.title as speciality_title',
                TABLE_UNIVER_FACULTY.'.title as faculty_title'
            )
            ->leftJoin(TABLE_UNIVER_SPECIALITY, TABLE_UNIVER_SPECIALITY.'.id', '=', TABLE_UNIVER_GROUP.'.speciality_id')
            ->leftJoin(TABLE_UNIVER_FACULTY, TABLE_UNIVER_FACULTY.'.id', '=', TABLE_UNIVER_SPECIALITY.'.faculty_id')
            ->orderBy(TABLE_UNIVER_GROUP.'.title')
            ->get();

        $this->setData($res);
    }

    public function forSubjectAction($groupCode)
    {
        $group = DB::table(TABLE_UNIVER_GROUP)->where('alias', '=', $groupCode)->first();

        $res = DB::table(TABLE_PREPOD_SUBJECT)
            ->select(
                TABLE_PREPOD_SUBJECT.'.*',
                DB::raw('count('.TABLE_STUDENT_MATERIAL.'.id) as material_count')
            )
            ->leftJoin(TABLE_STUDENT_MATERIAL, function($join) use ($group) {
                $join->on(TABLE_STUDENT_MATERIAL.'.subject_id', '=', TABLE_PREPOD_SUBJECT.'.id')
                    ->where(TABLE_STUDENT_MATERIAL.'.group_id', '=', $group['id'])
                    ->where(TABLE_STUDENT_MATERIAL.'.is_hidden', '=', 0);
            })
            ->where(TABLE_PREPOD_SUBJECT.'.user_id', '=', User::user()->getUID())
            ->groupBy(TABLE_PREPOD_SUBJECT.'.id')
            ->orderBy(TABLE_PREPOD_SUBJECT.'.title')
            ->get();

        if (!$group) {
            $this->setErrors('Группа не найдена', ERROR_ELEMENT_NOT_FOUND);
        }

        $this->setData($res);
        return $group;
    }

    public function forMaterialAction($groupCode, $subjectCode)
    {
        $group = $this->forSubjectAction($groupCode);
        if ($this->hasErrors(ERROR_ELEMENT_NOT_FOUND)) {
            $this->setData(null);
            return;
        }

        $subject = DB::table(TABLE_PREPOD_SUBJECT)
            ->where('alias', '=', $subjectCode)
            ->where('user_id', '=', User::user()->getUID())
            ->first();
        $res = DB::table(TABLE_STUDENT_MATERIAL)
            ->select(
                TABLE_STUDENT_MATERIAL.'.*',
                TABLE_PREPOD_MATERIAL.'.extension',
                TABLE_PREPOD_MATERIAL.'.size',
                TABLE_PREPOD_MATERIAL.'.filename'
            )
            ->leftJoin(TABLE_PREPOD_MATERIAL, TABLE_PREPOD_MATERIAL.'.id', '=', TABLE_STUDENT_MATERIAL.'.material_id')
            ->where(TABLE_STUDENT_MATERIAL.'.group_id', '=', $group['id'])
            ->where(TABLE_STUDENT_MATERIAL.'.subject_id', '=', $subject['id'])
            ->where(TABLE_STUDENT_MATERIAL.'.is_hidden', '=', 0)
            ->orderBy(TABLE_STUDENT_MATERIAL.'.date', 'desc')
            ->get();

        if (!$subject) {
            $this->setErrors('Дисципина не найдена', ERROR_ELEMENT_NOT_FOUND);
        }

        if ($this->isActionRequest('del_all')) {
            foreach ($this->_POST['i'] as $id) {
                if (!$id) {
                    continue;
                }

                $res = DB::table(TABLE_STUDENT_MATERIAL)
                    ->where('id', '=', $id)
                    ->where('group_id', '=', $group['id'])
                    ->where('subject_id', '=', $subject['id'])
                    ->first();

                if ($res) {
                    if (!empty($res['comment'])) {
                        DB::table(TABLE_STUDENT_MATERIAL)->delete($id);
                    } else {
                        DB::table(TABLE_STUDENT_MATERIAL)->updateOrInsert(['id' => $id], ['is_hidden' => 1]);
                    }
                }
            }
            Site::redirect(Site::getUrl());
        }

        $this->setData($res);

        return [
            'group' => $group,
            'subject' => $subject
        ];
    }

    public function forNewAction()
    {
        $data = $this->forMaterialAction($this->vars['group_code'], $this->vars['subject_code']);
        $group = $data['group'];
        $subject = $data['subject'];

        if ($this->hasErrors(ERROR_ELEMENT_NOT_FOUND)) {
            $this->setData(null);
            return;
        }

        // Общий список файлов
        $docList = DB::table(TABLE_PREPOD_MATERIAL)
            ->where('user_id', '=', User::user()->getUID())
            ->where('subject_id', '=', $subject['id'])
            ->get()
            ->toArray();
        $docList = array_reduce($docList, function($acc, $item){
            $acc[$item['id']] = $item['filename'] . '.' . $item['extension'];
            return $acc;
        }, []);

        // Текущий список файлов для группы
        $curActiveList = DB::table(TABLE_STUDENT_MATERIAL)
            ->select('material_id')
            ->where('group_id', '=', $group['id'])
            ->where('subject_id', '=', $subject['id'])
            ->where('is_hidden', '=', 0)
            ->get()
            ->toArray();
        $curActiveList = $activeList = array_reduce($curActiveList, function($acc, $item){
            $acc[] = $item['material_id'];
            return $acc;
        }, []);

        if ($this->isActionRequest()) {
            $this->clearErrors();

            $materials = $activeList = (array) array_values(array_intersect(array_keys($docList), $this->_POST['materials']));
            $unchanged = array_intersect($curActiveList, $materials);
            $fullMaterials = array_keys(array_flip($curActiveList) + array_flip($materials));
            $fullMaterials = array_diff($fullMaterials, $unchanged);

            $seekRow = [
                'group_id' => $group['id'],
                'subject_id' => $subject['id']
            ];

            foreach ($fullMaterials as $materialId) {
                $dataRow = [];
                $seekRow['material_id'] = $materialId;
                $isExists = DB::table(TABLE_STUDENT_MATERIAL)->where($seekRow)->exists();

                // Обновление
                if ($isExists) {
                    $dataRow['is_hidden'] = intval(in_array($materialId, $curActiveList));
                }
                // Создание
                else {
                    $dataRow += $seekRow;
                    $dataRow['is_hidden'] = 0;
                }

                $dataRow['date'] = Utilities::getDateTime();
                DB::table(TABLE_STUDENT_MATERIAL)->updateOrInsert($seekRow, $dataRow);
            }

            Site::redirect(Site::getModurl() . '/for/' . $group['alias'] . '/' . $subject['alias']);
        }

        $this->setData([
            'active_list' => $activeList,
            'doc_list' => $docList
        ]);
    }

    public function forNewCommentAction($v = array())
    {
        $data = $this->forMaterialAction($this->vars['group_code'], $this->vars['subject_code']);
        $group = $data['group'];
        $subject = $data['subject'];

        if ($this->hasErrors(ERROR_ELEMENT_NOT_FOUND)) {
            $this->setData(null);
            return;
        }

        if ($this->isActionRequest()) {
            $this->clearErrors();
            $v = $this->_POST;
            if (empty($v['comment'])) {
                $this->setErrors('Заполните комментарий');
            }

            if (!$this->hasErrors()) {
                $seekRow = [
                    'group_id' => $group['id'],
                    'subject_id' => $subject['id'],
                    'id' => $v['id']
                ];
                $dataRow = [
                    'group_id' => $group['id'],
                    'subject_id' => $subject['id'],
                    'comment' => $v['comment'],
                    'date' => Utilities::getDateTime(),
                    'is_hidden' => 0
                ];

                if (DB::table(TABLE_STUDENT_MATERIAL)->updateOrInsert($seekRow, $dataRow)) {
                    Site::redirect(Site::getModurl() . '/for/' . $group['alias'] . '/' . $subject['alias']);
                }
            }
        }

        $this->setData($v);
    }

    public function forEditCommentAction($id)
    {
        if (!$id) {
            return;
        }

        $v = DB::table(TABLE_STUDENT_MATERIAL)
            ->select(TABLE_STUDENT_MATERIAL.'.*')
            ->leftJoin(TABLE_UNIVER_GROUP, TABLE_UNIVER_GROUP.'.id', '=', TABLE_STUDENT_MATERIAL.'.group_id')
            ->leftJoin(TABLE_PREPOD_SUBJECT, TABLE_PREPOD_SUBJECT.'.id', '=', TABLE_STUDENT_MATERIAL.'.subject_id')
            ->where(TABLE_PREPOD_SUBJECT.'.user_id', '=', User::user()->getUID())
            ->where(TABLE_STUDENT_MATERIAL.'.id', '=', $id)
            ->where(TABLE_UNIVER_GROUP.'.alias', '=', $this->vars['group_code'])
            ->where(TABLE_PREPOD_SUBJECT.'.alias', '=', $this->vars['subject_code'])
            ->whereNotNull(TABLE_STUDENT_MATERIAL.'.comment')
            ->first();

        if (!$v['id']) {
            $this->setErrors('Комментарий не найден', ERROR_ELEMENT_NOT_FOUND);
        }

        $this->forNewCommentAction($v);
    }

    public function deleteAction($type, $id)
    {
        if (!($type && $id)) {
            return;
        }

        if ($type == 'my') {
            $res = DB::table(TABLE_PREPOD_MATERIAL)
                ->select(
                    TABLE_PREPOD_MATERIAL.'.*',
                    TABLE_PREPOD_SUBJECT.'.alias as subject_code'
                )
                ->leftJoin(TABLE_PREPOD_SUBJECT, TABLE_PREPOD_SUBJECT.'.id', '=', TABLE_PREPOD_MATERIAL.'.subject_id')
                ->where(TABLE_PREPOD_MATERIAL.'.user_id', '=', User::user()->getUID())
                ->where(TABLE_PREPOD_MATERIAL.'.id', '=', $id)
                ->first();

            if ($res) {
                @unlink(ROOT . $res['filepath']);
                DB::table(TABLE_PREPOD_MATERIAL)->delete($id);
                $back = '/my/' . $res['subject_code'];
            }
        }
        elseif ($type == 'for') {

            $res = DB::table(TABLE_STUDENT_MATERIAL)
                ->select(
                    TABLE_STUDENT_MATERIAL.'.comment',
                    TABLE_UNIVER_GROUP.'.alias as group_code',
                    TABLE_PREPOD_SUBJECT.'.alias as subject_code'
                )
                ->leftJoin(TABLE_PREPOD_MATERIAL, TABLE_PREPOD_MATERIAL.'.id', '=', TABLE_STUDENT_MATERIAL.'.material_id')
                ->leftJoin(TABLE_UNIVER_GROUP, TABLE_UNIVER_GROUP.'.id', '=', TABLE_STUDENT_MATERIAL.'.group_id')
                ->leftJoin(TABLE_PREPOD_SUBJECT, TABLE_PREPOD_SUBJECT.'.id', '=', TABLE_STUDENT_MATERIAL.'.subject_id')
                ->where(TABLE_PREPOD_SUBJECT.'.user_id', '=', User::user()->getUID())
                ->where(TABLE_STUDENT_MATERIAL.'.id', '=', $id)
                ->first();

            if ($res) {
                if (!empty($res['comment'])) {
                    DB::table(TABLE_STUDENT_MATERIAL)->delete($id);
                } else {
                    DB::table(TABLE_STUDENT_MATERIAL)->updateOrInsert(['id' => $id], ['is_hidden' => 1]);
                }
                $back = '/for/' . $res['group_code'] . '/' . $res['subject_code'];
            }
        }

        Site::redirect(Site::getModurl() . '/' . $back);
    }

    function fileDownload($docId)
    {
        $result = DB::table(TABLE_PREPOD_MATERIAL)
            ->where('user_id', '=', User::user()->getUID())
            ->where('id', '=', $docId)
            ->first();

        if (!$result) {
            return;
        }

        $file = ROOT . $result['filepath'];

        if (file_exists($file)) {
            // сбрасываем буфер вывода PHP, чтобы избежать переполнения памяти выделенной под скрипт
            // если этого не сделать файл будет читаться в память полностью!
            if (ob_get_level()) {
                ob_end_clean();
            }

            $filename = str_replace(' ', '_', $result['filename']) . '.' . $result['extension'];

            // заставляем браузер показать окно сохранения файла
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header("Content-Disposition: attachment; filename=" . $filename);
            header('Content-Transfer-Encoding: application/octet-stream');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Accept-Ranges: bytes');
            header('Content-Length: ' . filesize($file));

            // читаем файл и отправляем его пользователю
            readfile($file);
            exit;
        }
    }
}
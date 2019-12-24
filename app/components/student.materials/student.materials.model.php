<?php

namespace UTest\Components;

use UTest\Kernel\DB;
use UTest\Kernel\User\User;

class StudentMaterialsModel extends \UTest\Kernel\Component\Model
{
    public function subjectsAction()
    {
        $res = DB::table(TABLE_STUDENT_MATERIAL)
            ->select(
                TABLE_STUDENT_MATERIAL.'.id',
                TABLE_PREPOD_SUBJECT.'.title as subject_name',
                TABLE_PREPOD_SUBJECT.'.alias as subject_code',
                TABLE_USER.'.name as prepod_name',
                TABLE_USER.'.last_name as prepod_last_name',
                TABLE_USER.'.surname as prepod_surname',
                DB::raw('count('.TABLE_PREPOD_SUBJECT.'.id) as material_count')
            )
            ->leftJoin(TABLE_PREPOD_SUBJECT, TABLE_PREPOD_SUBJECT.'.id', '=', TABLE_STUDENT_MATERIAL.'.subject_id')
            ->leftJoin(TABLE_USER, TABLE_USER.'.id', '=', TABLE_PREPOD_SUBJECT.'.user_id')
            ->where(TABLE_STUDENT_MATERIAL.'.group_id', '=', User::user()->getGroupId())
            ->where(TABLE_STUDENT_MATERIAL.'.is_hidden', '=', 0)
            ->groupBy(TABLE_STUDENT_MATERIAL.'.subject_id')
            ->orderBy('subject_name')
            ->get();

        $this->setData($res);
    }

    public function materialsAction($subjectCode)
    {
        $subject = DB::table(TABLE_STUDENT_MATERIAL)
            ->select(TABLE_PREPOD_SUBJECT.'.id')
            ->leftJoin(TABLE_PREPOD_SUBJECT, TABLE_PREPOD_SUBJECT.'.id', '=', TABLE_STUDENT_MATERIAL.'.subject_id')
            ->where(TABLE_PREPOD_SUBJECT.'.alias', '=', $subjectCode)
            ->where(TABLE_STUDENT_MATERIAL.'.group_id', '=', User::user()->getGroupId())
            ->first();

        if (!$subject) {
            $this->setErrors('Предмет не найден', ERROR_ELEMENT_NOT_FOUND);
        } else {
            $res = DB::table(TABLE_STUDENT_MATERIAL)
                ->select(
                    TABLE_STUDENT_MATERIAL.'.*',
                    TABLE_PREPOD_MATERIAL.'.extension',
                    TABLE_PREPOD_MATERIAL.'.size',
                    TABLE_PREPOD_MATERIAL.'.filename'
                )
                ->leftJoin(TABLE_PREPOD_MATERIAL, TABLE_PREPOD_MATERIAL.'.id', '=', TABLE_STUDENT_MATERIAL.'.material_id')
                ->where(TABLE_STUDENT_MATERIAL.'.group_id', '=', User::user()->getGroupId())
                ->where(TABLE_STUDENT_MATERIAL.'.subject_id', '=', $subject['id'])
                ->where(TABLE_STUDENT_MATERIAL.'.is_hidden', '=', 0)
                ->orderBy(TABLE_STUDENT_MATERIAL.'.date', 'desc')
                ->get();
        }

        $this->setData($res);
    }

    function fileDownload($docId)
    {
        $result = DB::table(TABLE_STUDENT_MATERIAL)
            ->select(
                TABLE_STUDENT_MATERIAL.'.*',
                TABLE_PREPOD_MATERIAL.'.extension',
                TABLE_PREPOD_MATERIAL.'.filename',
                TABLE_PREPOD_MATERIAL.'.filepath'
            )
            ->leftJoin(TABLE_PREPOD_MATERIAL, TABLE_PREPOD_MATERIAL.'.id', '=', TABLE_STUDENT_MATERIAL.'.material_id')
            ->where(TABLE_STUDENT_MATERIAL.'.group_id', '=', User::user()->getGroupId())
            ->where(TABLE_STUDENT_MATERIAL.'.material_id', '=', $docId)
            ->where(TABLE_STUDENT_MATERIAL.'.is_hidden', '=', 0)
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
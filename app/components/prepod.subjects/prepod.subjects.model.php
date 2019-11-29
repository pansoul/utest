<?php

namespace UTest\Components;

use UTest\Kernel\DB;
use UTest\Kernel\User\User;
use UTest\Kernel\Utilities;
use UTest\Kernel\Site;

class PrepodSubjectsModel extends \UTest\Kernel\Component\Model
{
    public function subjectAction()
    {
        if ($this->isActionRequest('del_all')) {
            foreach ($this->_POST['i'] as $id) {
                DB::table(TABLE_PREPOD_SUBJECT)
                    ->where('id', '=', $id)
                    ->where('user_id', '=', User::user()->getUID())
                    ->delete();
            }

            Site::redirect(Site::getUrl());
        }

        $res = DB::table(TABLE_PREPOD_SUBJECT)
            ->where('user_id', '=', User::user()->getUID())
            ->orderBy('title')
            ->get();

        $this->setData($res);
    }

    public function newSubjectAction($v = array())
    {
        if ($this->isActionRequest()) {
            $this->clearErrors();
            $v = $this->_POST;
            if (!$v['title']) {
                $this->setErrors('Заполните название предмета');
            }

            if (!$this->hasErrors()) {
                $dataRow = [
                    'title' => $v['title'],
                    'alias' => Utilities::translit($v['title']),
                    'user_id' => User::user()->getUID()
                ];

                Utilities::checkUniq($dataRow['alias'], TABLE_PREPOD_SUBJECT);

                if (DB::table(TABLE_PREPOD_SUBJECT)->updateOrInsert(['id' => $v['id']], $dataRow)) {
                    Site::redirect(Site::getModurl());
                }
            }
        }

        $this->setData($v);
    }

    public function editAction($id)
    {
        $v = DB::table(TABLE_PREPOD_SUBJECT)
            ->where('id', '=', $id)
            ->where('user_id', '=', User::user()->getUID())
            ->first();

        if (!$v['id']) {
            $this->setErrors('Дисциплина не найдена', ERROR_ELEMENT_NOT_FOUND);
        }

        return $this->newSubjectAction($v);
    }

    public function deleteAction($id)
    {
        if (!$id) {
            return;
        }

        DB::table(TABLE_PREPOD_SUBJECT)
            ->where('id', '=', $id)
            ->where('user_id', '=', User::user()->getUID())
            ->delete();

        Site::redirect(Site::getModurl());
    }
}
<?php

namespace UTest\Components;

use UTest\Kernel\Utilities;
use UTest\Kernel\Site;
use UTest\Kernel\DB;

class AdminUniversityModel extends \UTest\Kernel\Component\Model
{
    public function facultyAction()
    {
        if ($this->isActionRequest('del_all')) {
            foreach ($this->_POST['i'] as $id) {
                DB::table(TABLE_UNIVER_FACULTY)->delete($id);
            }

            Site::redirect(Site::getUrl());
        }

        $res = DB::table(TABLE_UNIVER_FACULTY)->orderBy('title')->get();
        $this->setData($res);
    }

    public function newFacultyAction($v = array())
    {
        if ($this->isActionRequest()) {
            $this->clearErrors();
            $v = $this->_POST;
            if (!$v['title']) {
                $this->setErrors('Заполните название факультета');
            }

            if (!$this->hasErrors()) {
                $dataRow = [
                    'title' => $v['title'],
                    'alias' => Utilities::translit($v['title'])
                ];

                Utilities::checkUniq($dataRow['alias'], TABLE_UNIVER_FACULTY);

                if (DB::table(TABLE_UNIVER_FACULTY)->updateOrInsert(['id' => $v['id']], $dataRow)) {
                    Site::redirect(Site::getModurl());
                }
            }
        }

        $this->setData($v);
    }

    public function specialityAction($facultyCode)
    {
        if ($this->isActionRequest('del_all')) {
            foreach ($this->_POST['i'] as $id) {
                DB::table(TABLE_UNIVER_SPECIALITY)->delete($id);
            }
            Site::redirect(Site::getUrl());
        }

        $parent = DB::table(TABLE_UNIVER_FACULTY)->where('alias', '=', $facultyCode)->first();
        $res = DB::table(TABLE_UNIVER_SPECIALITY)->where('faculty_id', '=', $parent['id'])->orderBy('title')->get();

        if (!$parent) {
            $this->setErrors('Факультет не найден', ERROR_ELEMENT_NOT_FOUND);
        }

        $this->setData($res);
    }

    public function newSpecialityAction($v = array())
    {
        $this->specialityAction($this->vars['faculty_code']);
        if ($this->hasErrors(ERROR_ELEMENT_NOT_FOUND)) {
            $this->setData(null);
            return;
        }

        $parent = DB::table(TABLE_UNIVER_FACULTY)->where('alias', '=', $this->vars['faculty_code'])->first();
        $facultyList = DB::table(TABLE_UNIVER_FACULTY)->orderBy('title')->get()->toArray();
        $facultyList = array_reduce($facultyList, function($acc, $item){
            $acc[$item['id']] = $item['title'];
            return $acc;
        }, []);

        if ($this->isActionRequest()) {
            $this->clearErrors();
            $v = $this->_POST;
            $required = array(
                'title' => 'Заполните название специальности',
                'code' => 'Укажите код специальности',
                'faculty_id' => 'Выберите факультет'
            );

            foreach ($required as $k => $message) {
                if (empty($v[$k])) {
                    $this->setErrors($message);
                }
            }

            if (!$this->hasErrors()) {
                $dataRow = [
                    'title' => $v['title'],
                    'faculty_id' => isset($facultyList[$v['faculty_id']]) ? $v['faculty_id'] : $parent['id'],
                    'code' => $v['code']
                ];

                if (DB::table(TABLE_UNIVER_SPECIALITY)->updateOrInsert(['id' => $v['id']], $dataRow)) {
                    Site::redirect(Site::getModurl() . '/' . $this->vars['faculty_code']);
                }
            }
        } else {
            $v['faculty_id'] = $parent['id'];
        }

        $this->setData([
            'form' => $v,
            'faculty_list' => $facultyList
        ]);
    }

    public function editFacultyAction($id)
    {
        $v = DB::table(TABLE_UNIVER_FACULTY)->find($id);
        if (!$v['id']) {
            $this->setErrors('Факультет не найден', ERROR_ELEMENT_NOT_FOUND);
        }
        return $this->newFacultyAction($v);
    }

    public function editSpecialityAction($id)
    {
        $v = DB::table(TABLE_UNIVER_SPECIALITY)->find($id);
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
            DB::table(TABLE_UNIVER_FACULTY)->delete($id);
        } elseif ($type == 'speciality') {
            $facultyCode = DB::table(TABLE_UNIVER_SPECIALITY)
                ->select(TABLE_UNIVER_FACULTY.'.alias as faculty_code')
                ->leftJoin(TABLE_UNIVER_FACULTY, TABLE_UNIVER_FACULTY.'.id', TABLE_UNIVER_SPECIALITY.'.faculty_id')
                ->where(TABLE_UNIVER_SPECIALITY.'.id', '=', $id)
                ->first()['faculty_code'];
            DB::table(TABLE_UNIVER_SPECIALITY)->delete($id);
        }

        Site::redirect(Site::getModurl() . '/' . $facultyCode);
    }
}
<?php

namespace UTest\Components;

use \R;
use UTest\Kernel\Utilities;
use UTest\Kernel\Site;
use UTest\Kernel\AppBuilder;

class AdminUniversityModel extends \UTest\Kernel\Component\Model
{
    protected $checkMap = [
        'faculty_code' => [
            'table' => TABLE_UNIVER_FACULTY,
            'expr' => ''
        ]
    ];

    public function facultyAction()
    {
        if ($this->isActionRequest('del_all')) {
            foreach ($this->_POST['i'] as $item) {
                $res = R::load(TABLE_UNIVER_FACULTY, $item);
                R::trash($res);
            }

            Site::redirect(Site::getUrl());
        }

        $res = R::findAll(TABLE_UNIVER_FACULTY, 'ORDER BY title');
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
                $dataRow = $v['id']
                    ? R::load(TABLE_UNIVER_FACULTY, $v['id'])
                    : R::dispense(TABLE_UNIVER_FACULTY);
                $dataRow->title = $v['title'];
                $dataRow->alias = Utilities::translit($v['title']);

                Utilities::checkUniq($dataRow->alias, TABLE_UNIVER_FACULTY);

                if (R::store($dataRow)) {
                    Site::redirect(Site::getModurl());
                }
            }
        }

        $this->setData($v);
    }

    public function specialityAction($facultyCode)
    {
        if ($this->isActionRequest('del_all')) {
            foreach ($this->_POST['i'] as $item) {
                $res = R::load(TABLE_UNIVER_SPECIALITY, $item);
                R::trash($res);
            }
            Site::redirect(Site::getUrl());
        }

        $parent = R::findOne(TABLE_UNIVER_FACULTY, '`alias` = ?', array($facultyCode));
        $res = R::find(TABLE_UNIVER_SPECIALITY, 'faculty_id = ? ORDER BY title', array($parent->id));

        if (!$parent) {
            $this->setErrors('Факультет не найден', ERROR_ELEMENT_NOT_FOUND);
        }

        $this->setData($res);
    }

    public function newSpecialityAction($v = array())
    {
        $this->doAction('speciality', $this->vars['faculty_code'], true);
        if ($this->hasErrors(ERROR_ELEMENT_NOT_FOUND)) {
            $this->setData(null);
            return;
        }

        $in = '/' . $this->vars['faculty_code'];
        $r = R::findOne(TABLE_UNIVER_FACULTY, "`alias` = ?", (array)$this->vars['faculty_code']);
        if ($r) {
            $v['faculty_id'] = $r['id'];
            AppBuilder::addBreadcrumb($r['title'], Site::getModurl() . '/' . $r['alias']);
        }

        if ($this->isActionRequest()) {
            $this->clearErrors();
            $v = $this->_POST;
            $required = array(
                'title' => 'Заполните название специальности',
                'code' => 'Укажите код специальности'
            );

            foreach ($required as $k => $message) {
                if (empty($v[$k])) {
                    $this->setErrors($message);
                }
            }

            if (!$this->hasErrors()) {
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
                    Site::redirect(Site::getModurl() . $in);
                }
            }
        }

        $list = R::findAll(TABLE_UNIVER_FACULTY, 'ORDER BY title');
        $this->setData(array(
            'form' => $v,
            'faculty_list' => array_reduce($list, function($acc, $item){
                $acc[$item['id']] = $item['title'];
                return $acc;
            }, [])
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
        Site::redirect(Site::getModurl() . '/' . $toback);
    }
}
<?php

namespace UTest\Components;

use UTest\Kernel\Site;
use UTest\Kernel\DB;
use UTest\Kernel\Utilities;
use UTest\Kernel\User\User;

class AdminStudentsModel extends \UTest\Kernel\Component\Model
{
    const STUDENT_ROLE = 'student';

    public function groupAction()
    {
        if ($this->isActionRequest('del_all')) {
            foreach ($this->_POST['i'] as $id) {
                DB::table(TABLE_UNIVER_GROUP)->delete($id);
            }
            Site::redirect(Site::getUrl());
        }

        $res = DB::table(TABLE_UNIVER_GROUP)
            ->select(
                TABLE_UNIVER_GROUP.'.*',
                TABLE_UNIVER_SPECIALITY.'.title as speciality_name',
                DB::raw('count('.TABLE_USER.'.id) as students_count')
            )
            ->leftJoin(TABLE_UNIVER_SPECIALITY, TABLE_UNIVER_SPECIALITY.'.id', '=', TABLE_UNIVER_GROUP.'.speciality_id')
            ->leftJoin(TABLE_USER, TABLE_USER.'.group_id', '=', TABLE_UNIVER_GROUP.'.id')
            ->groupBy(TABLE_UNIVER_GROUP.'.id')
            ->get();

        $this->setData($res);
    }

    public function studentAction($groupCode)
    {
        if ($this->isActionRequest('del_all')) {
            foreach ($this->_POST['i'] as $id) {
                User::user()->delete($id);
            }
            Site::redirect(Site::getUrl());
        }

        if ($this->isActionRequest('newpass_all')) {
            foreach ($this->_POST['i'] as $id) {
                if (!intval($id) || $id == User::ADMIN_ID) {
                    continue;
                }

                $newpass = Utilities::getRandomString();
                $user = User::user()->edit(array('password' => $newpass), $id);
                if ($user) {
                    $users[] = $user;
                } else {
                    $this->setErrors(User::$last_errors);
                    break;
                }
            }
        }

        $parent = DB::table(TABLE_UNIVER_GROUP)->where('alias', '=', $groupCode)->first();
        $res = DB::table(TABLE_USER)->where('group_id', '=', $parent['id'])->get();

        if (!$parent) {
            $this->setErrors('Группа не найдена', ERROR_ELEMENT_NOT_FOUND);
        }

        $this->setData([
            'form' => $res,
            'users' => $users
        ]);
    }

    public function newGroupAction($v = array())
    {
        if ($this->isActionRequest()) {
            $this->clearErrors();
            $v = $this->_POST;
            $required = array(
                'title' => 'Заполните название группы',
                'speciality_id' => 'Укажите, к какой специальности относится группа'
            );
            
            foreach ($required as $k => $message) {
                if (empty($v[$k])) {
                    $this->setErrors($message);
                }
            }
            
            if (!$this->hasErrors()) {
                $dataRow = [
                    'title' => $v['title'],
                    'speciality_id' => $v['speciality_id'],
                    'alias' => Utilities::translit($v['title'])
                ];

                Utilities::checkUniq($dataRow['alias'], TABLE_UNIVER_GROUP);

                if ($r = DB::table(TABLE_UNIVER_GROUP)->updateOrInsert(['id' => $v['id']], $dataRow)) {
                    Site::redirect(Site::getModurl());
                }
            }
        }

        $specialityList = DB::table(TABLE_UNIVER_SPECIALITY)
            ->select(
                TABLE_UNIVER_SPECIALITY.'.*',
                TABLE_UNIVER_FACULTY.'.title as faculty_name'
            )
            ->leftJoin(TABLE_UNIVER_FACULTY, TABLE_UNIVER_FACULTY.'.id', '=', TABLE_UNIVER_SPECIALITY.'.faculty_id')
            ->orderBy(TABLE_UNIVER_SPECIALITY.'.faculty_id')
            ->orderBy(TABLE_UNIVER_SPECIALITY.'.title')
            ->get()
            ->toArray();

        $specialityList = array_reduce($specialityList, function($acc, $item){
            $acc[$item['id']] = '['.$item['faculty_name'].'] - '.$item['title'];
            return $acc;
        }, []);

        $this->setData([
            'form' => $v,
            'speciality_list' => $specialityList
        ]);
    }

    public function newStudentAction($v = array())
    {
        $this->studentAction($this->vars['group_code']);
        if ($this->hasErrors(ERROR_ELEMENT_NOT_FOUND)) {
            $this->setData(null);
            return;
        }

        $parent = DB::table(TABLE_UNIVER_GROUP)->where('alias', '=', $this->vars['group_code'])->first();
        $groupList = DB::table(TABLE_UNIVER_GROUP)->orderBy('title')->get()->toArray();
        $groupList = array_reduce($groupList, function($acc, $item){
            $acc[$item['id']] = $item['title'];
            return $acc;
        }, []);

        if ($this->isActionRequest()) {
            $this->clearErrors();
            $v = $this->_POST;
            $v['role'] = self::STUDENT_ROLE;
            $v['group_id'] = isset($groupList[$v['group_id']]) ? $v['group_id'] : $parent['id'];
            if ($v['id']) {
                $user = User::user()->edit($v, $v['id']);
                if ($user && empty($v['password'])) {
                    Site::redirect(Site::getModurl() . '/' . $parent['alias']);
                }
            } else {
                $user = User::user()->add($v);
            }
            $this->setErrors(User::$last_errors);
        } else {
            $v['group_id'] = $parent['id'];
        }

        $this->setData([
            'form' => $v,
            'group_list' => $groupList,
            'user' => $user
        ]);
    }

    public function editGroupAction($id)
    {
        $v = DB::table(TABLE_UNIVER_GROUP)->find($id);
        if (!$v['id']) {
            $this->setErrors('Группа не найдена', ERROR_ELEMENT_NOT_FOUND);
        }
        return $this->newGroupAction($v);
    }

    public function editStudentAction($id)
    {
        $v = User::getById($id);
        if (User::getRootGroup($v['role']) !== self::STUDENT_ROLE) {
            $this->setErrors('Пользователь не найден', ERROR_ELEMENT_NOT_FOUND);
        }
        return $this->newStudentAction($v);
    }

    public function deleteAction($type, $id)
    {
        if (!($type && $id)) {
            return;
        }

        if ($type == 'group') {
            DB::table(TABLE_UNIVER_GROUP)->delete($id);
        } elseif ($type == 'student') {
            $groupCode = DB::table(TABLE_USER)
                ->select(TABLE_UNIVER_GROUP.'.alias as group_code')
                ->leftJoin(TABLE_UNIVER_GROUP, TABLE_UNIVER_GROUP.'.id', TABLE_USER.'.group_id')
                ->where(TABLE_USER.'.id', '=', $id)
                ->first()['group_code'];
            User::user()->delete($id);
        }

        Site::redirect(Site::getModurl() . '/' . $groupCode);
    }
}
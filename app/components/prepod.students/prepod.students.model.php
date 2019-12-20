<?php

namespace UTest\Components;

use UTest\Kernel\DB;
use UTest\Kernel\User\User;
use UTest\Kernel\Utilities;

class PrepodStudentsModel extends \UTest\Kernel\Component\Model
{
    const STUDENT_ROLE = 'student';

    public function groupAction()
    {
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
        $users = [];

        if ($this->isActionRequest('newpass_all') && $this->isNativeActionMethod()) {
            foreach ($this->_POST['i'] as $id) {
                if (!intval($id) || $id == User::ADMIN_ID) {
                    continue;
                }

                $newpass = Utilities::getRandomString();
                $user = User::user()->doAction('admin', 'edit', ['password' => $newpass], $id);
                if ($user) {
                    $users[] = $user;
                } else {
                    $this->setErrors(User::getErrors());
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

        return $parent;
    }

    public function newStudentAction($v = array())
    {
        $group = $this->studentAction($this->vars['group_code']);
        if ($this->hasErrors(ERROR_ELEMENT_NOT_FOUND)) {
            $this->setData(null);
            return;
        }

        if ($this->isActionRequest()) {
            $this->clearErrors();
            $v = $this->_POST;
            if (empty(trim($v['password']))) {
                $this->setErrors('Пароль не может быть пустым');
            }
            if (!$this->hasErrors()) {
                $user = User::user()->doAction('admin', 'edit', ['password' => $v['password']], $this->vars['id']);
                if (!$user) {
                    $this->setErrors(User::getErrors());
                }
            }
        }

        $v['group_name'] = $group['title'];

        $this->setData([
            'form' => $v,
            'user' => $user
        ]);
    }

    public function editStudentAction($id)
    {
        $v = User::getById($id);
        if ($v['role'] !== self::STUDENT_ROLE) {
            $this->setErrors('Пользователь не найден', ERROR_ELEMENT_NOT_FOUND);
        }
        return $this->newStudentAction($v);
    }

}
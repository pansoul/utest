<?php

namespace UTest\Components;

use UTest\Kernel\Site;
use UTest\Kernel\User\User;
use UTest\Kernel\Utilities;
use UTest\Kernel\DB;

class AdminPrepodsModel extends \UTest\Kernel\Component\Model
{
    const PREPOD_ROLE = 'prepod';

    public function prepodAction()
    {
        $users = [];

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

        $res = DB::table(TABLE_USER)
            ->whereNull('group_id')
            ->where('role', '!=', User::ADMIN_ROLE)
            ->orderBy('last_name')
            ->get();

        $this->setData([
            'form' => $res,
            'users' => $users
        ]);
    }

    public function newPrepodAction($v = array())
    {
        if ($this->isActionRequest()) {
            $this->clearErrors();
            $v = $this->_POST;
            $v['role'] = self::PREPOD_ROLE;
            if ($v['id']) {
                $user = User::user()->edit($v, $v['id']);
                if ($user && empty($v['password'])) {
                    Site::redirect(Site::getModurl());
                }
            } else {
                $user = User::user()->add($v);
            }
            $this->setErrors(User::$last_errors);
        }

        $this->setData([
            'form' => $v,
            'user' => $user
        ]);
    }

    public function editAction($id)
    {
        $v = User::getById($id);
        if (User::getRootGroup($v['role']) !== self::PREPOD_ROLE) {
            $this->setErrors('Пользователь не найден', ERROR_ELEMENT_NOT_FOUND);
        }
        return $this->newPrepodAction($v);
    }

    public function deleteAction($id)
    {
        if (!$id) {
            return;
        }

        User::user()->delete($id);
        Site::redirect(Site::getModurl());
    }
}
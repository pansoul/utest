<?php

namespace UTest\Components;

use UTest\Kernel\Site;
use UTest\Kernel\User\User;
use UTest\Kernel\User\Roles\Prepod;
use UTest\Kernel\Utilities;
use UTest\Kernel\DB;

class AdminPrepodsModel extends \UTest\Kernel\Component\Model
{
    public function prepodAction()
    {
        $users = [];

        if ($this->isActionRequest('del_all') && $this->isNativeActionMethod()) {
            foreach ($this->_POST['i'] as $id) {
                User::user()->delete($id);
            }
            Site::redirect(Site::getUrl());
        }

        if ($this->isActionRequest('newpass_all') && $this->isNativeActionMethod()) {
            foreach ($this->_POST['i'] as $id) {
                if (!intval($id) || $id == User::ADMIN_ID) {
                    continue;
                }

                $newpass = Utilities::getRandomString();
                $user = User::user()->edit(array('password' => $newpass), $id);
                if ($user) {
                    $users[] = $user;
                } else {
                    $this->setErrors(User::getLastErrors());
                    break;
                }
            }
        }

        $res = DB::table(TABLE_USER)
            ->whereNull('group_id')
            ->where('role', Prepod::ROLE)
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
            $v['role'] = Prepod::ROLE;
            if ($v['id']) {
                $user = User::user()->edit($v, $v['id']);
                if ($user && empty($v['password'])) {
                    Site::redirect(Site::getModurl());
                }
            } else {
                $user = User::user()->add($v);
            }
            $this->setErrors(User::getLastErrors());
        }

        $this->setData([
            'form' => $v,
            'user' => $user
        ]);
    }

    public function editAction($id)
    {
        $v = User::getById($id);
        if ($v['role'] != Prepod::ROLE) {
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
<?php

namespace UTest\Components;

use \R;
use UTest\Kernel\Site;
use UTest\Kernel\User\Roles\Admin;
use UTest\Kernel\User\User;
use UTest\Kernel\Utilities;

class AdminPrepodsModel extends \UTest\Kernel\Component\Model
{
    const ROLE = 'prepod';

    // @todo
    private $arPosts = array(
        'old_prepod' => 'старший преподаватель',
        'docent' => 'доцент',
        'prof' => 'профессор',
        'prepod' => 'преподаватель',
    );

    public function prepodAction()
    {
        $users = array();

        if ($this->isActionRequest('del_all')) {
            foreach ($this->_POST['i'] as $id) {
                User::user()->delete($id);
            }
            Site::redirect(Site::getUrl());
        }

        if ($this->isActionRequest('newpass_all')) {
            foreach ($this->_POST['i'] as $id) {
                if (!intval($id) || $id == Admin::ADMIN_ID) {
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

        $res = R::find(TABLE_USER, 'group_id IS NULL AND role != "admin" ORDER BY last_name');
        $this->setData([
            'form' => $res,
            'users' => $users,
            'posts' => $this->arPosts
        ]);
    }

    public function newPrepodAction($v = array())
    {
        if ($this->isActionRequest()) {
            $this->clearErrors();
            $v = $this->_POST;
            $v['role'] = self::ROLE;
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
            'user' => $user,
            'posts' => $this->arPosts
        ]);
    }

//    public function editAction($id)
//    {
//        $v = R::load(TABLE_USER, $id);
//        if (User::getRootGroup($v['role']) !== 'prepod') {
//            $this->setErrors('Пользователь не найден', ERROR_ELEMENT_NOT_FOUND);
//        }
//        return $this->newPrepodAction($v);
//    }
//
//    public function deleteAction($id)
//    {
//        if (!$id) {
//            return;
//        }
//
//        $bean = R::load(TABLE_USER, $id);
//        if (User::getRootGroup($bean['role']) === 'prepod') {
//            R::trash($bean);
//        }
//        Site::redirect(Site::getModurl());
//    }
//
//    public function getarPosts()
//    {
//        return $this->arPosts;
//    }
}
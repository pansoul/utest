<?php

namespace UTest\Components;

use UTest\Kernel\Site;
use UTest\Kernel\User\User;

class IndexModel extends \UTest\Kernel\Component\Model
{
    public function indexAction()
    {
        if (User::isAuth()) {
            Site::redirect('/' . User::user()->getRole());
        }

        if ($this->isActionRequest()) {
            $success = User::login($this->_POST['login'], $this->_POST['pass']);
            if ($success) {
                Site::redirect('/' . User::user()->getRole(), false, 'Здравствуйте, ' . User::user()->getName() . '!');
            } else {
                $this->setErrors(User::getLastErrors());
            }
        }
    }
}

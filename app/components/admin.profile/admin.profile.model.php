<?php

namespace UTest\Components;

use UTest\Kernel\Site;
use UTest\Kernel\User\User;

class AdminProfileModel extends \UTest\Kernel\Component\Model
{
    public function indexAction()
    {
        if ($this->isActionRequest()) {
            if (empty($this->_POST['password'])) {
                $this->setErrors('Пароль не может быть пустым');
            } elseif (User::user()->edit(['password' => $this->_POST['password']])) {
                $_SESSION['update'] = 'Y';
                Site::redirect(Site::getModurl());
            } else {
                $this->setErrors(User::getLastErrors());
            }
        }
    }
}

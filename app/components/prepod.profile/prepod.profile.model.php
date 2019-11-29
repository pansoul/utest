<?php

namespace UTest\Components;

use UTest\Kernel\User\User;
use UTest\Kernel\Site;

class PrepodProfileModel extends \UTest\Kernel\Component\Model
{
    public function indexAction()
    {
        $v = User::user()->getFields([
            'last_name',
            'name',
            'surname',
            'post',
            'phone',
            'email'
        ]);

        if ($this->isActionRequest()) {
            $v = array_intersect_key($this->_POST, $v);

            if (User::user()->doAction('admin', 'edit', $v, User::user()->getUID())) {
                $_SESSION['update'] = 'Y';
                Site::redirect(Site::getModurl());
            }

            $this->setErrors(User::$last_errors);
        }

        $this->setData($v);
    }
}

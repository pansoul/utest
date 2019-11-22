<?php

namespace UTest\Components;

use \R;
use UTest\Kernel\Site;

class AdminSettingModel extends \UTest\Kernel\Component\Model
{
    const ID = 1;

    public function indexAction()
    {
        $data = R::load(TABLE_UNIVER_DATA, self::ID);

        if ($this->isActionRequest()) {
            $v = $this->_POST;
            $data->name = $v['univer_name'];
            $data->fullname = $v['univer_fullname'];
            $data->address = $v['address'];
            $data->phone = $v['~phone'];
            //$data->contacts = $v['contacts'];
            //$data->info = $v['~info'];

            if (R::store($data)) {
                $_SESSION['update'] = 'Y';
                Site::redirect(Site::getModurl());
            }
        }

        $this->setData([
            'univer_name' => $data->name,
            'univer_fullname' => $data->fullname,
            'address' => $data->address,
            'phone' => $data->phone,
        ]);
    }
}

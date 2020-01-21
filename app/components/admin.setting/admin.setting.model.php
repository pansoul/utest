<?php

namespace UTest\Components;

use UTest\Kernel\Site;
use UTest\Kernel\DB;
use UTest\Kernel\HttpRequest;

class AdminSettingModel extends \UTest\Kernel\Component\Model
{
    const ID = 1;

    public function indexAction()
    {
        $data = DB::table(TABLE_UNIVER_DATA)->find(self::ID);

        if ($this->isActionRequest()) {
            $v = $this->_POST;
            $dataRow = [
                'name' => $v['univer_name'],
                'fullname' => $v['univer_fullname'],
                'address' => $v['address'],
                'phone' => HttpRequest::convert2safe($v['~phone'], true),
                //'contacts' => $v['contacts'],
                //'info' => $v['~info']
            ];

            if (DB::table(TABLE_UNIVER_DATA)->where('id', '=', self::ID)->update($dataRow)) {
                $_SESSION['update'] = 'Y';
                Site::redirect(Site::getModurl());
            }
        }

        $this->setData($data);
    }

    public function univerAction($field)
    {
        $data = DB::table(TABLE_UNIVER_DATA)->find(self::ID);
        $this->setData($data[$field]);
    }
}

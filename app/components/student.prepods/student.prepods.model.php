<?php

namespace UTest\Components;

use UTest\Kernel\DB;
use UTest\Kernel\User\Roles\Prepod;

class StudentPrepodsModel extends \UTest\Kernel\Component\Model
{
    public function indexAction()
    {
        $res = DB::table(TABLE_USER)
            ->where('role', '=', Prepod::ROLE)
            ->orderBy('last_name')
            ->get();

        $this->setData($res);
    }
}
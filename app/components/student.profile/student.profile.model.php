<?php

namespace UTest\Components;

use UTest\Kernel\User\User;
use UTest\Kernel\Site;
use UTest\Kernel\DB;

class StudentProfileModel extends \UTest\Kernel\Component\Model
{
    public function indexAction()
    {
        $v = User::user()->getFields([
            'last_name',
            'name',
            'surname',
            'phone',
            'email',
            'group_id'
        ]);

        $data = DB::table(TABLE_UNIVER_GROUP)
            ->select(
                TABLE_UNIVER_GROUP.'.title as group_name',
                TABLE_UNIVER_SPECIALITY.'.title as speciality_name',
                TABLE_UNIVER_SPECIALITY.'.code as speciality_code',
                TABLE_UNIVER_FACULTY.'.title as faculty_name'
            )
            ->leftJoin(TABLE_UNIVER_SPECIALITY, TABLE_UNIVER_SPECIALITY.'.id', '=', TABLE_UNIVER_GROUP.'.speciality_id')
            ->leftJoin(TABLE_UNIVER_FACULTY, TABLE_UNIVER_FACULTY.'.id', '=', TABLE_UNIVER_SPECIALITY.'.faculty_id')
            ->where(TABLE_UNIVER_GROUP.'.id', '=', $v['group_id'])
            ->first();

        $u = [];

        if ($this->isActionRequest()) {
            $u = array_intersect_key($this->_POST, array_flip(['phone', 'email']));

            if (User::user()->doAction('admin', 'edit', $u, User::user()->getUID())) {
                $_SESSION['update'] = 'Y';
                Site::redirect(Site::getModurl());
            }

            $this->setErrors(User::getErrors());
        }

        $this->setData(array_merge($v, $data, $u));
    }
}

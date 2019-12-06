<?php

namespace UTest\Kernel\User\Roles;

use UTest\Kernel\DB;

class Student extends \UTest\Kernel\User\User
{
    const ROLE = 'student';

    public function __construct()
    {
        //
    }

    public function checkRunningTest($tid)
    {
        return Test::checkRunningUserTest($tid, self::$uid);
    }
}
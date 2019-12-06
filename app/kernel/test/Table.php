<?php]

namespace UTest\Kernel\Test;

use UTest\Kernel\DB;
use UTest\Kernel\User\User;

class Table
{
    private $uid = 0;
    private $errors = [];

    public function __construct($uid = 0, $tid = 0)
    {
        if (User::getById($uid)) {
            $this->uid = $uid;
        }
    }

    public function createOrEdit($v = [])
    {

    }

    public function delete($tid = 0)
    {

    }
}
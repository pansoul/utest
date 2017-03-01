<?php

class Student extends UUser {
    
    public function __construct()
    {
        //
    }
    
    public function checkRunningTest($tid) {
        return Test::checkRunningUserTest($tid, self::$uid);
    }
    
}

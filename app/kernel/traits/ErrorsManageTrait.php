<?php

namespace UTest\Kernel\Traits;

trait ErrorsManageTrait
{
    protected $errors = [];
    protected static $lastErrors = [];

    protected function clearErrors()
    {
        $e =& self::_isStatic() ? self::$lastErrors : $this->errors;
        $e = [];
    }

    protected function setErrors($errorsMsg = null)
    {
        $e =& self::_isStatic() ? self::$lastErrors : $this->errors;
        if (is_array($errorsMsg)) {
            $e = array_merge($e, $errorsMsg);
        } else {
            $e[] = $errorsMsg;
        }
    }

    public function getErrors()
    {
        $e =& self::_isStatic() ? self::$lastErrors : $this->errors;
        return $e;
    }

    public function hasErrors()
    {
        $e =& self::_isStatic() ? self::$lastErrors : $this->errors;
        return !empty($e);
    }

    protected static function _isStatic()
    {
        $bt = debug_backtrace();
        return $bt[1]['type'] == '::';
    }
}
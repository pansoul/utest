<?php

namespace UTest\Kernel\Traits;

trait ErrorsManageTrait
{
    protected $errors = [];
    protected static $lastErrors = [];

    protected function clearErrors()
    {
        $this->errors = [];
    }

    protected function setErrors($errorsMsg = null)
    {
        if (is_array($errorsMsg)) {
            $this->errors = array_merge($this->errors, $errorsMsg);
        } else {
            $this->errors[] = $errorsMsg;
        }
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function hasErrors()
    {
        return !empty($this->errors);
    }

    ///////////////////////
    
    protected static function clearLastErrors()
    {
        self::$lastErrors = [];
    }

    protected static function setLastErrors($errorsMsg = null)
    {
        if (is_array($errorsMsg)) {
            self::$lastErrors = array_merge(self::$lastErrors, $errorsMsg);
        } else {
            self::$lastErrors[] = $errorsMsg;
        }
    }

    public static function getLastErrors()
    {
        return self::$lastErrors;
    }

    public static function hasLastErrors()
    {
        return !empty(self::$lastErrors);
    }
}
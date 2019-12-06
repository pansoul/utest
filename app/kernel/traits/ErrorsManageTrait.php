<?php

namespace UTest\Kernel\Traits;

trait ErrorsManageTrait
{
    protected $errors = [];

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
}
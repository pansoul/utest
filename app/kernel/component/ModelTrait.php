<?php

namespace UTest\Kernel\Component;

trait ModelTrait
{
    protected $errors = [];
    protected $vars = [];
    protected $request = null;
    protected $data = null; // Данные, которые будут переданы в шаблон

    public $_POST = [];
    public $_GET = [];
    public $_REQUEST = [];
    public $debugInfo = [];

    public function isActionRequest($var = 'a', $value = 'Y')
    {
        return $this->_REQUEST[$var] == $value;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getErrors($errorCode = Model::ERROR_CODE_MAIN)
    {
        return $errorCode ? $this->errors[$errorCode] : $this->errors;
    }

    public function hasErrors($errorCode = Model::ERROR_CODE_MAIN)
    {
        return isset($this->errors[$errorCode]) && !empty($this->errors[$errorCode]);
    }

    public function getVars($key = null, $default = null)
    {
        if (is_array($key)) {
            return array_reduce($key, function($acc, $k){
                $acc[] = $this->getVars($k);
                return $acc;
            }, []);
        }
        if (null === $key) {
            return $this->vars;
        }
        return isset($this->vars[$key]) ? $this->vars[$key] : $default;
    }

    public function getRequest()
    {
        return $this->request;
    }
}


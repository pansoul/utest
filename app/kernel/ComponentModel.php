<?php

namespace UTest\Kernel;

class ComponentModel
{
    const ACTION_DEFAULT = 'index';
    const ERROR_CODE_DEFAULT = 'main';

    protected $errors = [];
    protected $vars = [];
    protected $request = null;
    protected $data = null; // Данные, которые будут переданы в шаблон

    public $_POST = [];
    public $_GET = [];
    public $_REQUEST = [];
    public $debugInfo = [];

    public function __construct()
    {
        $this->request = new HttpRequest();
        $this->_GET = $this->request->getValue(HttpRequest::GET);
        $this->_POST = $this->request->getValue(HttpRequest::POST);
        $this->_REQUEST = $this->request->getValue(HttpRequest::REQUEST);
    }

    public function indexAction()
    {
        //
    }

    final public function doAction($action = self::ACTION_DEFAULT, $args = array())
    {
        $method = $action . 'Action';
        $args = (array) $args;

        if (Base::getConfig('debug > component_debug')) {
            ob_start();
            echo "<pre>";
            var_dump($args);
            echo "</pre>";
            $argsDebug = ob_get_clean();

            $bt = debug_backtrace();
            $caller = array_shift($bt);
            $this->debugInfo['do_action']['value'][] = array(
                'method' => $method,
                'file' => $caller['file'],
                'line' => $caller['line'],
                'args' => $argsDebug
            );
        }

        if (is_callable([$this, $method])) {
            call_user_func_array([$this, $method], $args);
        }
    }

    final public function isActionRequest($var = 'a', $value = 'Y')
    {
        return $this->_REQUEST[$var] == $value;
    }

    final public function setData($data = null)
    {
        $this->data = $data;
    }

    final public function getData()
    {
        return $this->data;
    }

    final public function setErrors($errorsMsg = null, $errorCode = self::ERROR_CODE_DEFAULT)
    {
        $this->errors[$errorCode] = $errorsMsg;
    }

    final public function getErrors($errorCode = self::ERROR_CODE_DEFAULT)
    {
        return $errorCode ? $this->errors[$errorCode] : $this->errors;
    }

    final public function hasErrors($errorCode = self::ERROR_CODE_DEFAULT)
    {
        return isset($this->errors[$errorCode]);
    }

    final public function clearErrors()
    {
        $this->errors = [];
    }

    final public function setVars()
    {
        // @todo
    }

    final public function getVars()
    {
        // @todo
    }

    final public function getRequest()
    {
        return $this->request;
    }
}

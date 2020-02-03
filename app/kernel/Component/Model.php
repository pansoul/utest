<?php

namespace UTest\Kernel\Component;

use UTest\Kernel\Base;
use UTest\Kernel\Errors\DoActionException;

class Model extends TemplateResult
{
    const ACTION_DEFAULT = 'index';
    const ERROR_CODE_MAIN = 'main';

    public function indexAction()
    {
        $this->setData(func_get_args());
    }

    final public function doAction($action = self::ACTION_DEFAULT, $args = array())
    {
        $method = $this->getConvertedActionMethodName($action);
        $args = (array) $args;

        if (Base::getConfig('debug > component_debug')) {
            ob_start();
            echo "<pre>"; var_dump($args); echo "</pre>";
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
        } else {
            throw new DoActionException("Запускаемый метод '{$method}' не найден");
        }
    }

    final public function setData($data = null)
    {
        $this->data = $data;
    }

    final public function setErrors($errorsMsg = null, $errorCode = self::ERROR_CODE_MAIN)
    {
        if (!isset($this->errors[$errorCode])) {
            $this->errors[$errorCode] = [];
        }
        if (is_array($errorsMsg)) {
            $this->errors[$errorCode] = array_merge($this->errors[$errorCode], $errorsMsg);
        } else {
            $this->errors[$errorCode][] = $errorsMsg;
        }
    }

    final public function clearErrors()
    {
        $this->errors = [];
    }

    final public function setVars($key = null, $value = null)
    {
        if (is_array($key)) {
            $this->vars = array_merge($this->vars, $key);
        } else {
            $this->vars[$key] = $value;
        }
    }

    final public function isNativeActionMethod($methodName = null)
    {
        if (is_null($methodName)) {
            $methodName = debug_backtrace()[1]['function'];
        }

        return strcasecmp($methodName, $this->getConvertedActionMethodName($this->action)) == 0;
    }

    private function getConvertedActionMethodName($action = '')
    {
        $action = str_replace('-', '_', $action);
        $exploded = explode('_', $action);
        $method = join('', $exploded) . 'Action';
        return $method;
    }
}

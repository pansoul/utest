<?php

namespace UTest\Kernel\Component;

use UTest\Kernel\HttpRequest;
use UTest\Kernel\Base;
use UTest\Kernel\Form;

class TemplateResult
{
    protected $errors = [];
    protected $vars = [];
    protected $request = null;
    protected $data = null; // Глобальные данные, которые будут переданы в шаблон. Собираются внтури методов модели

    public $_POST = [];
    public $_GET = [];
    public $_REQUEST = [];
    public $debugInfo = [];

    protected $componentName = '';
    protected $action = '';

    public function __construct($componentName = '', $action = '', $data = null, $errors = [], $vars = [], &$debugInfo = [])
    {
        $this->componentName = $componentName;
        $this->action = $action;
        $this->data = $data;
        $this->errors = $errors;
        $this->vars = $vars;
        $this->debugInfo =& $debugInfo;
        $this->request = new HttpRequest();
        $this->_GET = $this->request->getValue(HttpRequest::GET);
        $this->_POST = $this->request->getValue(HttpRequest::POST);
        $this->_REQUEST = $this->request->getValue(HttpRequest::REQUEST);
    }

    final public function includeTemplate($templateName, $data = null, $errors = null, $vars = null)
    {
        $tpl = clone $this;
        $tpl->errors = !is_null($errors) ? $errors : $this->getErrors(false);
        $tpl->data = !is_null($data) ? $data : $this->getData();
        $tpl->vars = !is_null($vars) ? $vars : $this->getVars();

        $templateName = strtolower($templateName);
        $templatePath = COMPONENTS_PATH . '/' . $tpl->componentName . '/views/' . $templateName . '.phtml';

        if (Base::getConfig('debug > component_debug')) {
            $bt = debug_backtrace();
            $caller = array_shift($bt);
            $tpl->debugInfo['template']['value'][] = array(
                'template' => $templateName,
                'file' => $caller['file'],
                'line' => $caller['line']
            );
        }

        if (!file_exists($templatePath)) {
            return Form::warning("Шаблон '{$templateName}' не найден");
        }

        // Набор локальных переменных для быстрого доступа внутри шаблона
        $request = $tpl->_REQUEST;
        $post = $tpl->_POST;
        $get = $tpl->_GET;
        $errors = $tpl->getErrors(false);
        $data = $tpl->getData();
        $vars = $tpl->getVars();

        // Общий резалт со старой структурой
        $arResult = [
            'errors' => $errors,
            'data' => $data,
            'request' => $request,
            'vars' => $vars
        ];

        ob_start();
        include $templatePath;
        $view = ob_get_clean();

        unset($tpl);

        return $view;
    }

    final public function isActionRequest($var = 'a', $value = 'Y')
    {
        return $this->_REQUEST[$var] == $value;
    }

    final public function getData()
    {
        return $this->data;
    }

    final public function getErrors($errorCode = Model::ERROR_CODE_MAIN)
    {
        return $errorCode ? $this->errors[$errorCode] : $this->errors;
    }

    final public function hasErrors($errorCode = Model::ERROR_CODE_MAIN)
    {
        return isset($this->errors[$errorCode]) && !empty($this->errors[$errorCode]);
    }

    final public function getVars($key = null, $default = null)
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

    final public function getRequest()
    {
        return $this->request;
    }
}

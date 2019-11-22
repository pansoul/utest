<?php

namespace UTest\Kernel\Component;

use UTest\Kernel\HttpRequest;
use UTest\Kernel\Base;
use UTest\Kernel\Form;

class TemplateResult
{
    use ModelTrait;

    protected $componentName = '';

    public function __construct($componentName = '', $data = null, $errors = [], $vars = [], &$debugInfo = [])
    {
        $this->componentName = $componentName;
        $this->data = $data;
        $this->errors = $errors;
        $this->vars = $vars;
        $this->debugInfo =& $debugInfo;
        $this->request = new HttpRequest();
        $this->_GET = $this->request->getValue(HttpRequest::GET);
        $this->_POST = $this->request->getValue(HttpRequest::POST);
        $this->_REQUEST = $this->request->getValue(HttpRequest::REQUEST);
    }

    final public function includeTemplate($templateName)
    {
        $templatePath = COMPONENTS_PATH . '/' . $this->componentName . '/views/' . $templateName . '.phtml';

        if (Base::getConfig('debug > component_debug')) {
            $bt = debug_backtrace();
            $caller = array_shift($bt);
            $this->debugInfo['template']['value'][] = array(
                'template' => $templateName,
                'file' => $caller['file'],
                'line' => $caller['line']
            );
        }

        if (!file_exists($templatePath)) {
            return Form::warning("Шаблон '{$templateName}' не найден");
        }

        // Набор локальных переменных для быстрого доступа внутри шаблона
        $request = $this->_REQUEST;
        $post = $this->_POST;
        $get = $this->_GET;
        $errors = $this->getErrors(false);
        $data = $this->getData();
        $vars = $this->getVars();

        // Общий резалт со старой структурой
        $arResult = [
            'errors' => $this->getErrors(false),
            'data' => $this->getData(),
            'request' => $this->getRequest(),
            'vars' => $this->getVars()
        ];

        ob_start();
        include $templatePath;
        $view = ob_get_clean();

        return $view;
    }
}

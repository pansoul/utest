<?php

namespace UTest\Kernel;

use UTest\Kernel\Component\Controller;
use UTest\Kernel\Errors\AppException;

class AppBuilder
{
    const INC_FILE_NAME = '_inc.php';

    private static $title = '';
    private static $h = '';
    private static $content = '';
    private static $arBreadcrumb = array();

    private $html = null;
    private $arSysTplVars = array(
        'content',
        'menu',
        'title',
        'h'
    );

    public function build($content, $layout)
    {
        if ($layout == LAYOUT_404) {
            Site::setHeader404();
        }

        self::$content = (string) $content;

        $tplVars = $this->loadVars();
        $this->html = $this->loadLayout(Base::getConfig('theme'), $layout, $tplVars);
    }

    private function loadLayout($theme, $layout, $tplVars)
    {
        $arFind = $arReplace = array();

        $themePath = THEMES_PATH . '/' . $theme;
        if (!is_dir($themePath)) {
            throw new AppException("Тема '{$theme}' не найдена");
        }

        $includedLayout = pathinfo($layout, PATHINFO_EXTENSION) ? true : false;
        $layoutPath = $themePath . '/' . $layout . ($includedLayout ? '' : '.html');
        if (!file_exists($layoutPath)) {
            if ($includedLayout) {
                return Form::notice("Подключаемый шаблон '{$layout}' не найден");
            } else {
                throw new AppException("Основной шаблон '{$layout}' не найден");
            }
        }

        $template = file_get_contents($layoutPath);
        preg_match_all("/\{!([.-_a-z0-9\/]+)\}/i", $template, $arInc, PREG_SET_ORDER); // подключаемые шаблоны
        preg_match_all("/\[!([-_a-z]+)\]/i", $template, $arVars, PREG_SET_ORDER); // переменные

        foreach ($arInc as $item) {
            $arFind[] = $item[0];
            $arReplace[] = $this->loadLayout($theme, $item[1], $tplVars);
        }

        foreach ($arVars as $item) {
            $arFind[] = $item[0];
            if (array_key_exists($item[1], $tplVars)) {
                $arReplace[] = $tplVars[$item[1]];
            } else {
                $arReplace[] = Form::notice("переменная '{$item[1]}' не объявлена");
            }
        }

        return str_replace($arFind, $arReplace, $template);
    }

    private function loadVars()
    {
        $tplVars = array();
        foreach ($this->arSysTplVars as $v) {
            $func = 'get' . ucfirst($v);
            $tplVars[$v] = self::$func();
        }

        $incFile = THEMES_PATH . '/' . Base::getConfig('theme') . '/' . self::INC_FILE_NAME;
        if (file_exists($incFile)) {
            $incTplVars = require_once $incFile;
            $tplVars = array_merge($tplVars, (array) $incTplVars);
        }

        return $tplVars;
    }

    public function show()
    {
        if (!$this->html) {
            throw new AppException('Шаблон не построен!');
        }
        echo $this->html;
    }

    public static function getTitle()
    {
        return self::$title;
    }

    public static function setTitle($value)
    {
        self::$title = (string) $value;
    }

    public static function getH()
    {
        return self::$h;
    }

    public static function setH($value)
    {
        self::$h = (string) $value;
    }

    public static function getContent()
    {
        return self::$content;
    }

    public static function getMenu()
    {
        return Controller::loadComponent('utility', 'menu');
    }

    public static function addBreadcrumb($name = '', $url = '')
    {
        if (!$name || !$url) {
            return false;
        }

        self::$arBreadcrumb[] = array(
            'name' => (string) $name,
            'url' => (string) $url
        );

        return true;
    }

    public static function editBreadcrumbItem($index = 0, $name = '', $url = '')
    {
        if (!isset(self::$arBreadcrumb[$index]) || !$name || !$url) {
            return false;
        }

        self::$arBreadcrumb[$index] = array(
            'name' => (string) $name,
            'url' => (string) $url
        );

        return true;
    }

    public static function getBreadcrumb()
    {
        return self::$arBreadcrumb;
    }

    public static function clearBreadcrumb()
    {
        self::$arBreadcrumb = array();
    }

    // @todo заменить везде вызовы компонентов через данный алиас
    public static function loadComponent($args = '', $action = false, $actionArgs = array(), $routeMap = array())
    {
        return Controller::loadComponent($args, $action, $actionArgs, $routeMap);
    }
}
<?php

namespace UTest\Kernel;

use UTest\Kernel\User\User;
use Utest\Kernel\Errors\AppException;
use UTest\Kernel\Component\Controller;

class AppRouter
{
    private $request;

    public function __construct()
    {
        $this->request = new HttpRequest();
        $this->fillSiteData();
    }

    public function parse()
    {
        $builder = new AppBuilder();

        // Найдем шаблон для текущей страницы
        $layout = $this->getLayoutPage(Site::getUrl());

        // Синхронизируемся с картой url-алиасов из настроек
        // @todo проверить позже
        if ($argsConfig = Base::getConfig('url_aliases > ' . Site::getUrl())) {
            if (is_array($argsConfig)) {
                if ($argsConfig[1] !== false) {
                    if (!User::isAuth()) {
                        $builder->build(false, LAYOUT_404);
                    }
                } elseif ($argsConfig[0] === false) {
                    $builder->build(false, $layout);
                } else {
                    $builder->build(Controller::loadComponent($argsConfig[0], true), $layout);
                }
            }
        }
        // Если текущего алиаса в карте не найдено, значит загружем контент исходя из авторизации и типа пользователя
        // @todo произвести рефактор условия
        elseif (Site::getGroup() && (!User::isAuth() || Site::getGroup() != User::user()->getRole())) {
            $builder->build(false, LAYOUT_404);
        } else {
            $builder->build(Controller::loadComponent(Site::getUrl()), $layout);
        }

        $builder->show();
    }

    private function getLayoutPage($url)
    {
        if (Base::getConfig('tpl_map > ' . $url)) {
            return (string) Base::getConfig('tpl_map > ' . $url);
        } elseif (Base::getConfig('tpl_map > *')) {
            return (string) Base::getConfig('tpl_map > *');
        } else {
            throw new AppException('Не задан шаблон по умолчанию');
        }
    }

    // @todo продумать над связкой функций [fillSiteData, setModData]
    private function fillSiteData()
    {
        // Сформируем корректный и унифицированный url для дальнейшей работы
        $url = explode('?', $this->request->getServer('REQUEST_URI'), 2);
        $url = array_filter(explode('/', strtolower($url[0])));
        $url = '/' . implode('/', $url);

        // Найдём группу из url
        $exploded = explode('/', $url, 4);
        $group = @$exploded[1];

        Site::setUrl($url);
        Site::setGroup($group);

        self::setModData($url);

        if (User::isAuth()) {
            // @todo установить константы url'ов и др. данных пользователя
        }
    }

    // @todo продумать над связкой функций [fillSiteData, setModData]
    public static function setModData($url = '')
    {
        // Найдём имя компонента и акшн-параметры из url
        $exploded = explode('/', $url, 4);
        $group = @$exploded[1];
        $controller = @$exploded[2];
        $args = @$exploded[3];

        $componentName = $group ? $group : 'index';
        if ($controller) {
            $componentName .= '.' . $controller;
        }

        if ($args) {
            $args = '/' . $args;
        }

        Site::setModParamsRow($args);
        Site::setModName($componentName);
        Site::setModUrl('/' . str_replace('.', '/', $componentName));
    }
}

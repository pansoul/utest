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
        elseif (Site::getGroup() && (!User::isAuth() || Site::getGroup() != User::user()->getRoleRootGroup())) {
            $builder->build(false, LAYOUT_404);
        } else {
            $builder->build(Controller::loadComponent(Site::getModParamsRow()), $layout);
        }

        $builder->show();
    }

    private function fillSiteData()
    {
        // Сформируем корректный и унифицированный алиас для дальнейшей работы
        $url = explode('?', $this->request->getServer('REQUEST_URI'), 2);
        $url = array_filter(explode('/', strtolower($url[0])));
        $url = '/' . implode('/', $url);

        // Найдём переданную [группу] и [контроллер-акшн-параметры], если таковы имеются
        $exploded = explode('/', strtolower($url), 3);
        $group = $exploded[1];
        $args = @$exploded[2];

        Site::setUrl($url);
        Site::setGroup($group);
        Site::setModParamsRow($args);

        if (User::isAuth()) {
            // @todo установить константы url'ов и др. данных пользователя
        }
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
}

<?php

class URouter { 
    
    public function __construct()
    {           
        // Сформируем корректный и унифицированный алиас для дальнейшей работы
        $url = explode('?', $_SERVER['REQUEST_URI'], 2);                                
        $url = explode('/', strtolower($url[0]));                     
        $url = array_filter($url);                              
        USite::setUrl('/' . implode('/', $url));  
    }
    
    public function parse()
    {           
        // Найдём переданную [группу] и [контроллер-акшн-параметры], 
        // если таковы имеются
        $exploded = explode('/', strtolower(USite::getUrl()), 3);         
        $group = $exploded[1];        
        $args = @$exploded[2];                
        USite::setGroup($group);
    
        $builder = new UAppBuilder();          
        
        // Найдем шаблон для даннной страницы
        $layout = $this->getLayoutPage(USite::getUrl());            
        
        // Синхронизируемся с картой url-алиасов из настроек
        if (UBase::getConfig('urlAliases')) {                
            $argsConfig = UBase::getConfig('urlAliases > ' . USite::getUrl());                            
            if (is_array($argsConfig)) {                      
                if ($argsConfig[1] !== false) {                    
                    if (!UUser::isAuth()) {
                        $builder->build(ERROR_404, '404');
                        $builder->show();
                        return;
                    }
                }
                if ($argsConfig[0] === false) {
                    $builder->build(null, $layout);
                } else {
                    $builder->build(USiteController::loadComponent($argsConfig[0], true), $layout);
                }
                $builder->show();
                return;
            }            
        }   
        
        // Если текущего алиаса в карте не найдено, значит загружем контент
        // исходя из авторизации и типа пользователя                
        if ($group && (!UUser::isAuth() || $group != UUser::user()->getRoleRootGroup())) {            
            $builder->build(ERROR_404, '404');
        } else {               
            $builder->build(USiteController::loadComponent($args), $layout);
        }
        
        $builder->show();
    }
    
    private function getLayoutPage($url)
    {
        if (UBase::getConfig('tplMap > ' . $url)) {
            return (string) UBase::getConfig('tplMap > ' . $url);
        } elseif (UBase::getConfig('tplMap > *')) {
            return (string) UBase::getConfig('tplMap > *');
        } else {
            throw new UAppException('Не задан шаблон по умолчанию');
        }
    }
}

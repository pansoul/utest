<?php

class USiteController {
    
    /** 
     * Значение заголовка по умолчанию
     */
    const DEFAULT_TITLE = 'Default title';
    
    /**
     * Собственно, в этой переменной будет располагаться результат работы компонента
     * @var string
     */
    private $modContent;
    
    /**
     * Содержит имя текущего загруженного компонента
     * @var string
     */
    protected $componentName;
    
    /**
     * Действующий акшн для компонента
     * @var string 
     */
    protected $action;  
    
    /**
     * Массив параметров для загружаемого акшна. 
     * Массив используется при ручном вызове компонентов
     * @var array
     */
    protected $actionArgs = array();
    
    /**
     * Действующие параметры для компонента
     * @var string 
     */
    protected $params;
    
    /**
     * Модель текущего загруженного компонента
     * @var object
     */
    protected $model;
    
    /**
     * Маршрутизатор для компонентов
     * 
     * Может принимать следующие параметры:
     * <ul>
     *      <li>actionDefault - какой акшн запускать по умолчанию (по умолчанию: "index")</li>
     *      <li>setTitle - какой заголовок компонента установить (по умолчанию: null)</li>
     *      <li>addBreadcrumb - добавлять ли название компонента в навигационную цепочку (по умолчанию: true)</li>
     *      <li>
     *          paramsPath - массив параметров для определенных акшнов<br/>
     *          пример:<br/>
     *          <pre>
     *          ...
     *          'paramsPath' => array(
     *              'delete' => '/<type>/<id>',
     *              ...
     *          )
     *          ...
     *          </pre>
     *          В данном примере для акшна "delete" устанавливаются две переменные 'type' и 'id'.<br/>
     *          Таким образом, url вида http://utest.ru/admin/students/delete/group/1 
     *          возвратит для акшна "delete" переменные 'type'='group' и 'id'=1
     *      </li>
     *      <li>
     *          params - массив правил для определенных параметров<br/>
     *          Каждое из правил может содержать по три свойства:
     *          <ul>
     *              <li>mask - маска параметра, при чем само значение параметра должен быть указано как "<?>"</li>
     *              <li>rule - регулярное выражение для валидации значения параметра</li>
     *              <li>default - значение параметра по умолчанию</li>
     *          </ul>
     *          пример:<br/>
     *          <pre>
     *          ...
     *          'params' => array(
     *              'id' => array(
     *                  'mask' => 'page_<?>',
     *                  'rule' => '[0-9]',
     *                  'default' => 1
     *              ),
     *              ...
     *          )
     *          ...
     *          </pre>
     *          В данном примере для параметра 'id' задается правило, которое выцепляет значение 
     *          'id' из url вида http://utest.ru/admin/students/show/page_7. 
     *          Проверяет найденное значение по регулярному выражению. Если значение
     *          не проходит валидацию по регулярному выражению, то в параметр запишется false.
     * </ul>
     * @var array
     */
    protected $routeMap = array(        
        'actionDefault' => 'index',
        'setTitle' => null,
        'addBreadcrumb' => true,
        'paramsPath' => array(),
        'params' => array(
            'id' => array(
                'mask' => '',
                'rule' => '[0-9]',
                'default' => 0,
            )
        )
    );

    /**
     * Первым делом контроллер Сайта определяет имя запршиваемого компонента
     * и загружает его
     * 
     * @param string $args - может быть либо частью строки url, либо точным названием запрашиваемого компонета
     * @param bool $parentAction - во избежания рекурсивного вызова в контроллере компонента укзываем данный параметр
     * @param string $action - имя экшена, что будет запущен при вызове компонента
     * @param array $actionArgs - массив переданных параметров для $action
     * @return void
     */
    private function __construct($args = null, $parentAction = false, $action = null, $actionArgs = array())
    {
        if (!$parentAction)
            return;        

        if ($action) {
            $componentName = $args;
            $this->action = $action;  
        } else {
            $componentName = USite::getGroup() ? USite::getGroup() : 'index';
            UAppBuilder::addBreadcrumb('Информер', '/'.USite::getGroup());
            if (!empty($args)) {                
                $componentPiece = explode('/', $args, 2);
                $componentName .= '.' . $componentPiece[0];                   
                list($this->action, $this->params) = explode('/', $componentPiece[1], 2);
            }
        }        
        USite::setModname($componentName);
        USite::setModurl('/' . str_replace('.', '/', $componentName));
        $this->modContent = $this->load($componentName, $actionArgs);
    }

    /**
     * Даёт команду "старт" работе компонента
     * @return string
     */
    public function run()
    {   
        $arResult = $this->model->doAction($this->action, $this->actionArgs);        
        return $this->loadView('default', $arResult);
    }

    /**
     * Создаёт экземпляр контроллера Сайта для последующей загрузки нужного компонента
     * @param string $args - может быть либо частью строки url, либо точным названием запрашиваемого компонета (если указан параметр $action)<br/>
     *                      Если $action не указан, приложение будет пытаться загрузить компонент,
     *                      определенный для url, указанный в данном параметре
     * @param bool|string $action - имя акшна, что будет запущен при вызове компонента
     * @param array $actionArgs - массив переданных параметров для $action
     * @return string
     */
    final public static function loadComponent($args, $action = false, array $actionArgs = array())
    {  
        $thisComponent = new self($args, true, $action, $actionArgs);           
        return $thisComponent->modContent;        
    }

    /**
     * Загружает запрашиваемый компонент и возвращает результат его работы
     * @param string $componentName
     * @param array $actionArgs
     * @return string
     */
    private function load($componentName, $actionArgs)
    {      
        // Расположение компонента
        $component_path = APP_PATH . '/components/' . $componentName;        
        @list($_group, $_module) = explode('.', $componentName, 2);        
        // Класс контроллера компонента
        $componentController = ucfirst($_group) . ucfirst($_module) . 'Controller';
        // Расположение класса контроллера
        $componentController_path = $component_path . '/' . $componentName . '.controller.php';        
        // Класс модели компонента
        $componentModel = ucfirst($_group) . ucfirst($_module) . 'Model';
        // Расположение класса модели
        $componentModel_path = $component_path . '/' . $componentName . '.model.php';

        if (!is_dir($component_path))
            return USiteErrors::warning("Компонет '$componentName' не найден");

        if (!file_exists($componentController_path))
            return USiteErrors::warning("Контроллер компонента '$componentName' не найден");

        if (!file_exists($componentModel_path))
            return USiteErrors::warning("Модель компонента '$componentName' не найдена");
        
        // подключаем Контроллер и Модель компонента
        require_once $componentController_path;
        require_once $componentModel_path;
        
        if (!class_exists($componentController))
            return USiteErrors::warning("Класс контроллера компонента '$componentName' не указан");
        
        if (!class_exists($componentModel))
            return USiteErrors::warning("Класс модели компонента '$componentName' не указан");        
       
        $component = new $componentController();    
        $component->componentName = $componentName;        
        $component->action = $this->action;  
        $component->actionArgs = $actionArgs;
        $component->params = $this->params ? '/' . $this->params : null;             
        $component->model = new $componentModel();        
        $method = $component->action . 'Action';
        $component->routeMap = array_merge($this->routeMap, $component->routeMap);
        if (!is_callable(array($component->model, $method))) {              
            $component->params = $component->action ? '/' . $component->action . $component->params : null;                                  
            $component->action = $component->routeMap['actionDefault'];            
        }                                      
        $component->model->vars = $this->parseParamsVars($component);
        if ($component->routeMap['addBreadcrumb'])
            UAppBuilder::addBreadcrumb($component->routeMap['setTitle'], USite::getModurl());
        $title = $component->routeMap['setTitle'] ? $component->routeMap['setTitle'] : self::DEFAULT_TITLE;                 
        if (!UAppBuilder::getTitle() || $component->routeMap['setTitle'])            
            UAppBuilder::setTitle($title);            
        if (!UAppBuilder::getH() || $component->routeMap['setTitle'])            
            UAppBuilder::setH($title);            
        
        return $component->run();
    }
    
    /**
     * Загружает шаблон компонента
     * @param string $template - название шаблона, если не передано имя, то 
     *                          возьмется название шаблона по умолчанию "default"
     * @param array $arResult - передаваемые параметры в шаблон. Переменная представляет
     *                          собой массив с 4-мя ключами<br/>
     *  <ul>
     *      <li>
     *          [errors] - содержит массив ошибок выполнения
     *                      методов компонента (к примеру, неверный
     *                      логин или пароль)
     *      </li>
     *      <li>
     *          [data] - содержит необходимые данные для
     *                      заполнения шаблона. Может представлять из 
     *                      себя любой тип данных
     *      </li>  
     *      <li>
     *          [request] - Содержит объект данных запроса
     *      </li>
     *      <li>
     *          [vars] - Содержит массив значений переменных, объявленных через контроллер
     *      </li>  
     *  </ul>   
     * @return string
     */
    protected function loadView($template, $arResult)
    {   
        $templateName = $template ? (string) $template : 'default';
        $template_path = APP_PATH . '/components/' . $this->componentName . '/views/' . $templateName . '.phtml';        
        
        if (!file_exists($template_path))
            return USiteErrors::warning("Шаблон '$templateName' не найден");        
        
        ob_start();        
        include $template_path;
        $view = ob_get_contents();         
        ob_end_clean();
        
        return $view;
    }
    
    
    final private function parseParamsVars($component)
    { 
        $vars = array();
        $finderPath = '';        
        
        if (empty($component->routeMap['params']) 
            || empty($component->routeMap['paramsPath']) 
            || !is_array($component->routeMap['paramsPath'])
        ) {            
            return $vars;
        } elseif (is_null($component->params) || !array_key_exists($component->action, $component->routeMap['paramsPath'])) {            
            foreach ($component->routeMap['params'] as $var => $values)
            {
                $vars[$var] = $values['default'];
            }
        } else { 
            $finderPath = $component->routeMap['paramsPath'][$component->action];                
            $paramsExploded = array_values(array_filter(explode('/', $component->params)));
            $pathExploded = array_values(array_filter(explode('/', $finderPath)));                       
            foreach ($pathExploded as $k => $vs)
            {
                $varName = (substr($vs, 1, -1));
                $varValue = $paramsExploded[$k];
                $thatMapper = $component->routeMap['params'][$varName];                    
                
                if (empty($thatMapper))
                    $vars[$varName] = $varValue;
                else {
                    if (!isset($paramsExploded[$k]))
                        $vars[$varName] = $thatMapper['default'];
                    else {
                        if (!empty($thatMapper['mask'])) {                                                    
                            $maskExploded = array_filter(explode('<?>', $thatMapper['mask'], 2));                                                        
                            $varValue = str_replace($maskExploded, '', $varValue);                                                        
                            if (strcasecmp(str_replace('<?>', $varValue, $thatMapper['mask']), $paramsExploded[$k]) !== 0) {                                    
                                $vars[$varName] = false;                                
                                continue;
                            }                            
                        }
                        if (!empty($thatMapper['rule'])) {                            
                            if (!preg_match("/^{$thatMapper['rule']}+$/", $varValue)) {
                                $vars[$varName] = false;
                                continue;
                            }
                        }
                        $vars[$varName] = $varValue;
                    }
                }
            }
        }
        return $vars;
    }
}
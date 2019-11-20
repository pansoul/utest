<?php

namespace UTest\Kernel;

class ComponentController
{
    /**
     * Имя дефолтного шаблона компонетов
     */
    const TEMPLATE_NAME_DEFAULT = 'default';
    
    /** 
     * Значение заголовка по умолчанию
     */
    const DEFAULT_TITLE = 'Default title';
    
    /**
     * Собственно, в этой переменной будет располагаться результат работы компонента
     * @var array
     */
    protected $content = [];
    
    /**
     * Содержит имя текущего загруженного компонента
     * @var string
     */
    protected $componentName = '';
    
    /**
     * Найденный акшн компонента
     * @var string 
     */
    protected $action = '';
    
    /**
     * Массив параметров для вызываемого акшна. 
     * Заполняется при ручном вызове компонена.
     * @var array
     */
    protected $actionArgs = array();
    
    /**
     * Текущая строка параметров компонента.
     * Заполняется при вызове компонента из url.
     * @var string 
     */
    protected $paramsRow = '';
    
    /**
     * Модель текущего загруженного компонента
     * @var object
     */
    protected $model = null;
    
    /**
     * Диагностическая информация о компоненте.
     * Заполняется при включенной опции component_debug.
     * @var array
     */
    protected $debugInfo = array(
        'call' => array(
            'name' => 'Вызов [component] &rarr; [action]',
            'value' => null
        ),
        'vars' => array(
            'name' => 'Переменные акшна (vars)',
            'value' => null
        ),
        'do_action' => array(
            'name' => 'Стек вызываемых методов',
            'value' => null
        ),
        'template' => array(
            'name' => 'Стек вызываемых шаблонов',
            'value' => null
        )        
    );
    
    /**
     * Маршрутизатор для компонентов
     * 
     * Может принимать следующие параметры:
     * <ul>
     *      <li>
     *          setTitle - какой заголовок компонента установить.<br/> 
     *          По умолчанию null
     *      </li>
     *      <li>
     *          actionMain - какой акшн запускать, если строка парамтеров пуста 
     *          (другими словами, "корневой" запуск компонента).<br/> 
     *          По умолчанию 'index'
     *      </li>
     *      <li>
     *          actionDefault - какой акшн запускать, если не удалось найти ни 
     *          одного акшна для текущей строки параметров (поиск осуществляется 
     *          в actionsPath).<br/> 
     *          По умолчанию null
     *      </li>
     *      <li>
     *          addBreadcrumb - добавлять ли название компонента в навигационную цепочку.<br/>
     *          По умолчанию true
     *      </li>
     *      <li>
     *          actionsPath - Список акшнов для конкретной строки параметров.<br/>
     *          По умолчанию array()
     * 
     *          <b>Примечание!</b> Строка параметров - это часть url-строки, следующей после имени компонента.
     *          Если же компонет запускается вручную, то строка парметров всегда будет пустой.
     * 
     *          Значения описываемых акшнов могут быть как обычной строкой,
     *          содержащей точный путь, так и быть частью регулярного выражения 
     *          с содержанием переменных.<br/>
     *          Ключами являются имена акшнов, которые должны быть запущены по определенной
     *          строке параметров, указанной в значении.
     * 
     *          <b>Примечание!</b> Имена акшнов необходимо указывать без постфикса Action,
     *          который обязателен только в имени метода в модели компонента.
     * 
     *          Изначально парсер пробует найти акшн по полному совпадению строки.
     *          Если акшн не найден, поиск осуществляется через регулярные выражения
     *          с раскрытием переменных. Переменные указываются в угловых скобках
     *          <имя_переменной>, имя переменной должно соответствовать правилу [-_a-zA-Z0-9]+.<br/>
     *          Если правило раскрытия переменной не указано в varsRule, то переменная раскрывается 
     *          по правилу [^\/]+.<br/>
     *          В конечно итоге раскрытая строка параметров, описанная в значении акшна,
     *          подставляется в регулярное выражение /^раскрытая_строка$/<br/>
     * 
     *          <b>Примечание!</b> Запускается всегда первый найденный акшн.
     *          
     *          Пример:<br/>
     *          <pre>
     *          ...
     *          'actionsPath' => array(
     *              'show' => '/full/path',
     *              'delete' => '/<type>/<id>',
     *              'print' => '.*?/print',
     *              ...
     *          )
     *          ...
     *          </pre>
     *      </li>
     *      <li>
     *          varsRule - список кастомных правил раскрытия переменных, определенных в строке параметров<br/>
     *          По умолчанию array()
     *          
     *          Ключом должно быть имя переменной, определённой в строке парметров в actionsPath.
     *          А значением строка, выраженная частью регулярного выражения.
     *          
     *          пример:<br/>
     *          <pre>
     *          ...
     *          'varsRule' => array(
     *              'type' => '[a-zA-Z]',
     *              'id' => '[0-9]'
     *              ...
     *          )
     *          ...
     *          </pre>     
     * </ul>
     * @var array
     */
    protected $routeMap = array();
    
    /**
     * Маршрутизатор для компонентов по умолчанию
     * @var array
     */
    private static $routeMapDefault = array(        
        'setTitle' => null,
        'addBreadcrumb' => true,
        'actionMain' => 'index',
        'actionDefault' => null,
        'actionsPath' => array(),
        'varsRule' => array()
    );

    /**
     * Первым делом контроллер сайта определяет имя запршиваемого компонента и загружает его.
     *
     * @param string $componentName - может быть либо частью строки url, либо точным названием запрашиваемого компонета     
     * @param string $action - имя экшена, что будет запущен при вызове компонента
     * @param array $actionArgs - массив переданных параметров для $action
     * @param array $paramsRow - строка параметров
     * @param array $routeMap
     *
     * @return void
     */
    private function __construct($componentName, $action = false, $actionArgs = array(), $paramsRow = null, $routeMap = array())
    {
        if ($action) {
            Site::setModname($componentName);
            Site::setModurl('/' . str_replace('.', '/', $componentName));
        }
        
        $this->componentName = $componentName;        
        $this->action = $action;
        $this->actionArgs = $actionArgs;
        $this->paramsRow = $paramsRow ? '/' . $paramsRow : null;        
        $this->routeMap = array_merge(self::$routeMapDefault, $this->routeMap, $routeMap);                        
        $this->routeMap['actionsPath'] = array_map('trim', $this->routeMap['actionsPath']);        
    }

    /**
     * Даёт команду "старт" работе компонента, выполняя требуемый акшн.<br/>
     * В этот момент весь компонент уже полностью собран.<br/>
     * Должен возвращать итоговый контент, выводимый пользователю.
     *
     * @return string
     */
    public function run()
    {   
        $arResult = $this->model->doAction($this->action, $this->actionArgs);        
        $content = $this->loadView('default', $arResult);
        $this->putContent($content);
    }

    /**
     * Загружает запрашиваемый компонент и возвращает результат его работы.
     *
     * @param string $args - может быть либо частью строки url, либо точным названием запрашиваемого компонета (если указан параметр $action)
     * @param bool|string $action - имя акшна, что будет запущен при вызове компонента
     * @param array $actionArgs - массив переданных параметров для $action 
     * @param array $routeMap - переопределение части или всего маршрутизатора загружаемого компонента
     *
     * @return string
     */
    final public static function loadComponent($args, $action = false, $actionArgs = array(), $routeMap = array())
    {  
        if ($action) {                        
            $componentName = $args;              
        } else {
            $componentName = Site::getGroup() ? Site::getGroup() : 'index';            
            AppBuilder::addBreadcrumb('Информер', '/'.Site::getGroup());            
            if (!empty($args)) {                
                $componentPiece = explode('/', $args, 2);                                
                $componentName .= '.' . $componentPiece[0];                   
                $paramsRow = $componentPiece[1];                
            }
        }
        
        $includeResult = self::includeComponentFiles($componentName);
        if ($includeResult !== true) {
            return $includeResult;
        }
        
        // Формируем имена классов, которые будем подключать
        @list($group, $module) = explode('.', $componentName, 2);
        $groupPieces = explode('_', $group);
        $modulePieces = explode('_', $module);
        $groupPieces = array_map(function($value){
            return ucfirst($value);
        }, $groupPieces);
        $modulePieces = array_map(function($value){
            return ucfirst($value);
        }, $modulePieces);
        $className = implode('', $groupPieces) . implode('', $modulePieces);        
                
        // Класс контроллера компонента
        $componentController = '\\UTest\\Components\\' . $className . 'Controller';
        // Класс модели компонента
        $componentModel = '\\UTest\\Components\\' . $className . 'Model';
        
        if (!class_exists($componentController)) {
            return Form::warning("Класс контроллера {$componentController} компонента '{$componentName}' не найден");
        }        
        if (!class_exists($componentModel)) {
            return Form::warning("Класс модели {$componentModel} компонента '{$componentName}' не найден");
        }
        
        $component = new $componentController($componentName, $action, $actionArgs, $paramsRow, $routeMap);                
        $component->init($componentModel);

        if (Base::getConfig('debug > component_debug') && $componentName != 'component_debug') {
            $component->putContent(SiteController::loadComponent('component_debug', true, array($component->debugInfo)), false);
        }

        return $component->getContent();
    }
    
    /**
     * Подключает файлы, необходимые для загрузки компонента
     * @param string $componentName - имя компонента
     * @return boolean|string
     */
    private static function includeComponentFiles($componentName)
    {
        // Расположение компонента
        $componentPath = COMPONENTS_PATH . '/' . $componentName;
        // Расположение класса контроллера
        $componentControllerPath = $componentPath . '/' . $componentName . '.controller.php';
        // Расположение класса модели
        $componentModelPath = $componentPath . '/' . $componentName . '.model.php';

        if (!is_dir($componentPath)) {
            return Form::warning("Компонет '{$componentName}' не найден");
        }
        if (!file_exists($componentControllerPath)) {
            return Form::warning("Файл контроллера компонента '{$componentName}' не найден");
        }
        if (!file_exists($componentModelPath)) {
            return Form::warning("Файл модели компонента '{$componentName}' не найдена");
        }

        require_once $componentControllerPath;
        require_once $componentModelPath;
        
        return true;
    }
   
    private function init($componentModel)
    {
        $this->model = new $componentModel();                        
        
        // Если строка параметров пуста, то запускаем главный акшн
        if ($this->action === true || (!$this->paramsRow && !$this->action)) {            
            $this->action = $this->routeMap['actionMain'];               
        } 
        // Пытаемся найти акшн по точному совпадению
        elseif ($action = array_search($this->paramsRow, $this->routeMap['actionsPath'])) {
            $this->action = $action;
        }
        // Если акшн не найден, то пробуем его найти, проверяя пути через регулярные выражения.
        // Также парсим объявленные переменные в адресе пути акшна.
        else {   
            $arDebugRules = array(); // @todo or not todo... 
            foreach ($this->routeMap['actionsPath'] as $action => $path)
            {   
                $rule = str_replace('\\/', '/', $path);
                $rule = str_replace('/', '\\/', $rule);
                preg_match_all("/<([-_a-z0-9]+)>/i", $path, $arVars, PREG_SET_ORDER);                 
                
                if ($arVars) {
                    foreach ($arVars as $matches)
                    {
                        $rule = str_replace(
                            $matches[0], 
                            isset($this->routeMap['varsRule'][$matches[1]]) ? '('.$this->routeMap['varsRule'][$matches[1]].'+?)' : '([^\/]+?)', 
                            $rule
                        );                          
                    }  
                    
                    if (preg_match("/^{$rule}$/", $this->paramsRow, $vars)) {                        
                        unset($vars[0]);
                        $vars = array_values($vars);                        
                        foreach ($vars as $k => $v)
                        {
                            $this->model->vars[$arVars[$k][1]] = $v;
                        }
                        $this->action = $action;
                        break 1;
                    }
                } 
                elseif (preg_match("/^{$rule}$/", $this->paramsRow)) {
                    $this->action = $action;
                    break;
                }
                
                $arDebugRules[] = "[{$action}] => {$rule}";
            }            
        }
        
        // Если акшн так и не найден, но указан акшн по умолчанию, то указываем его
        if (!$this->action && $this->routeMap['actionDefault']) {
            $this->action = $this->routeMap['actionDefault'];
        }    
        
        if (Base::getConfig('debug > component_debug')) {
            $this->debugInfo['call']['value'] = "[{$this->componentName}] &rarr; [{$this->action}]";
            ob_start();    
            echo "<pre>"; var_dump($this->model->vars); echo "</pre>";
            $this->debugInfo['vars']['value'] = ob_get_clean();             
            $this->model->debugInfo =& $this->debugInfo;
        }                            
        
        if ($this->routeMap['addBreadcrumb']) {
            AppBuilder::addBreadcrumb($this->routeMap['setTitle'], Site::getModurl());
        }
        $title = $this->routeMap['setTitle'] ? $this->routeMap['setTitle'] : self::DEFAULT_TITLE;        
        if (!AppBuilder::getTitle() || $this->routeMap['setTitle']) {
            AppBuilder::setTitle($title);            
        }
        if (!AppBuilder::getH() || $this->routeMap['setTitle']) {
            AppBuilder::setH($title);            
        } 
        
        if (!$this->action) {
            $content = Form::error("Action для данного адреса не найден");            
            $this->putContent($content);
            $this->putContent($this->paramsRow, false);
            $this->putContent(Form::error($arDebugRules), false);
            return;
        }        
        $method = $this->action . 'Action';
        if (!is_callable(array($this->model, $method))) {                          
            $content = Form::error("Запускаемый метод '{$method}' в компоненте '{$this->componentName}' не найден");
            $this->putContent($content);
            return;
        } 
        
        $this->run();
    }

    /**
     * Загружает шаблон компонента
     *
     * @param string $templateName
     * @param null $data
     *
     * @return string
     */
    protected function loadView($templateName = self::TEMPLATE_NAME_DEFAULT, $data = null)
    {   
        $templateName = $templateName ? (string) $templateName : self::TEMPLATE_NAME_DEFAULT;
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

        if (!is_null($data)) {
            $this->model->setData($data, true);
        }

        // Набор локальных переменных для быстрого доступа внутри шаблона
        $request = $this->model->_REQUEST;
        $post = $this->model->_POST;
        $get = $this->model->_GET;
        $errors = $this->model->getErrors(false);
        $data = $this->model->getData();
        $vars = $this->model->getVars();

        // Общий резалт со старой структурой
        $arResult = [
            'errors' => $this->model->getErrors(false),
            'data' => $this->model->getData(),
            'request' => $this->model->getRequest(),
            'vars' => $this->model->getVars()
        ];

        ob_start();        
        include $templatePath;
        $view = ob_get_clean();                 
        
        return $view;
    }

    /**
     * Сохраняет переданный контент как результат работы компонента
     *
     * @param string $content
     * @param bool $overwrite
     */
    protected function putContent($content = '', $overwrite = true)
    {
        if ($overwrite) {
            $this->content = [$content];
        } else {
            $this->content[] = $content;
        }
    }
    
    /**
     * Возвращает сохраненный результат работы компонента
     * @return array
     */
    protected function getContent()
    {
        return join('', $this->content);
    }

    /**
     * По сути алиас для доступа к аналогичному методу модели компонента
     *
     * @param string $action
     * @param array $args
     */
    protected function doAction($action = ComponentModel::ACTION_DEFAULT, $args = [])
    {
        $this->model->doAction($action, $args);
    }
}
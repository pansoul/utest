<?php

namespace UTest\Kernel\Component;

use UTest\Kernel\AppRouter;
use UTest\Kernel\Base;
use UTest\Kernel\Site;
use UTest\Kernel\AppBuilder;
use UTest\Kernel\Form;
use UTest\Kernel\Errors\DoActionException;

class Controller
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
            'name' => 'Переменные строки параметров (vars)',
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
     *          title - какой заголовок компонента установить.<br/>
     *          По умолчанию null
     *      </li>
     *      <li>
     *          action_main - какой акшн запускать, если строка парамтеров пуста.
     *          Другими словами, "корневой" запуск компонента.<br/>
     *          По умолчанию 'index'
     *      </li>
     *      <li>
     *          actionDefault - какой акшн запускать, если имеется строка параметров, но не удалось найти ни
     *          одного акшна для неё (поиск осуществляется в actionsPath).<br/>
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
    private $routeMapDefault = array(
        'title' => null,
        'add_breadcrumb' => false,
        'action_main' => 'index',
        'action_default' => null,
        'actions_params' => [],
        'vars_rules' => []
    );

    /**
     * Первым делом контроллер сайта определяет имя запршиваемого компонента и загружает его.
     *
     * @param string $componentName - может быть либо частью строки url, либо точным названием запрашиваемого компонета     
     * @param bool|string $action - имя экшена, что будет запущен при вызове компонента
     * @param array $actionArgs - массив переданных параметров для $action
     * @param array $routeMap
     *
     * @return void
     */
    private function __construct($componentName = '', $action = false, $actionArgs = array(), $routeMap = array())
    {
        $this->componentName = $componentName;        
        $this->action = $action;
        $this->actionArgs = $actionArgs;
        $this->paramsRow = Site::getModParamsRow();

        $actionsParams = array_merge_recursive(
            $this->routeMapDefault['actions_params'],
            (array) @$this->routeMap()['actions_params'],
            (array) @$routeMap['actions_params']
        );
        $this->routeMap = array_merge($this->routeMapDefault, (array) $this->routeMap(), (array) $routeMap);
        $this->routeMap['actions_params'] = $actionsParams;
    }

    protected function routeMap()
    {
        return $this->routeMap;
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
        $this->model->doAction($this->action, $this->actionArgs);
        $content = $this->loadView(self::TEMPLATE_NAME_DEFAULT);
        $this->putContent($content);
    }

    /**
     * Загружает запрашиваемый компонент и возвращает результат его работы.
     *
     * @param string $args - может быть либо строкой url (/group/modName/action/...) главного компонента страницы,
     * либо точным названием запрашиваемого компонета (при указанном параметре $action)
     * @param bool|string $action - имя акшна, что будет запущен при вызове компонента
     * @param array $actionArgs - массив переданных параметров для $action 
     * @param array $routeMap - переопределение части или всего маршрутизатора загружаемого компонента
     *
     * @return string
     */
    final public static function loadComponent($args = '', $action = false, $actionArgs = array(), $routeMap = array())
    {  
        if ($action) {                        
            $componentName = $args;
        } else {
            AppRouter::setModData($args);
            AppBuilder::addBreadcrumb('Информер', '/'.Site::getGroup());
            $componentName = Site::getModName();
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
        
        $component = new $componentController($componentName, $action, $actionArgs, $routeMap);
        $component->init($componentModel);

        if (Base::getConfig('debug > component_debug') && $componentName != 'component_debug') {
            $component->putContent(self::loadComponent('component_debug', true, array($component->debugInfo)));
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
        $isMainAction = $this->action === true || (!$this->paramsRow && !$this->action);

        if ($isMainAction) {
            $this->action = $this->routeMap['action_main'];
            $actionParams = $this->routeMap;
        } else {
            $arDebugRules = [];
            $actionParams = $this->findActionParamsInRouteMap($this->paramsRow, $arDebugRules);

            if ($actionParams['action']) {
                $this->action = $actionParams['action'];
            } elseif ($this->routeMap['action_default']) {
                $this->action = $this->routeMap['action_default'];
            }
        }

        if (Base::getConfig('debug > component_debug')) {
            $this->debugInfo['call']['value'] = "[{$this->componentName}] &rarr; [{$this->action}]";
            ob_start();
            echo "<pre>"; var_dump($this->model->getVars()); echo "</pre>";
            $this->debugInfo['vars']['value'] = ob_get_clean();
            $this->model->debugInfo =& $this->debugInfo;
        }

        $title = $actionParams['title']
            ? $actionParams['title']
            : ($this->routeMap['title'] ? $this->routeMap['title'] : self::DEFAULT_TITLE);

        if ($this->routeMap['add_breadcrumb']) {
            AppBuilder::addBreadcrumb($this->routeMap['title'] ? $this->routeMap['title'] : self::DEFAULT_TITLE, Site::getModUrl());
        }
        if (!AppBuilder::getTitle() || $actionParams['title']) {
            AppBuilder::setTitle($title);
        }
        if (!AppBuilder::getH() || $actionParams['title']) {
            AppBuilder::setH($title);
        }

        $paramsRowSplitted = array_filter(explode('/', $this->paramsRow));
        $locParamsRow = '';
        foreach ($paramsRowSplitted as $param) {
            $locParamsRow .= '/' . $param;
            $locActionParams = $this->findActionParamsInRouteMap($locParamsRow);
            if ($locActionParams) {
                if ($locActionParams['add_breadcrumb']) {
                    AppBuilder::addBreadcrumb($locActionParams['title'], Site::getModurl() . $locParamsRow);
                }
            }
        }
        
        if (!$this->action) {
            $this->putContent(Form::error("Action для строки параметров <b>{$this->paramsRow}</b> не найден"));
            $this->putContent(Form::error($arDebugRules));
            return;
        }

        // @todo может можно как-то обойтись без отлова исключения?
        try {
            $this->run();
        } catch (DoActionException $e) {
            $this->putContent(Form::error($e->getMessage() . " в компонете '{$this->componentName}'"));
        }
    }

    private function findActionParamsInRouteMap($paramsRow = '', &$arDebugRules = [])
    {
        $actionParams = [];
        $arDebugRules = [];

        if (isset($this->routeMap['actions_params'][$paramsRow])) {
            $actionParams = $this->routeMap['actions_params'][$paramsRow];
        } else {
            foreach ($this->routeMap['actions_params'] as $path => $params)
            {
                $rule = str_replace('\\/', '/', $path);
                $rule = str_replace('/', '\\/', $rule);
                preg_match_all("/<([-_a-z0-9]+)>/i", $path, $arVars, PREG_SET_ORDER);

                if ($arVars) {
                    foreach ($arVars as $matches)
                    {
                        $rule = str_replace(
                            $matches[0],
                            isset($this->routeMap['vars_rules'][$matches[1]]) ? '('.$this->routeMap['vars_rules'][$matches[1]].'+?)' : '([^\/]+?)',
                            $rule
                        );
                    }

                    if (preg_match("/^{$rule}$/", $paramsRow, $vars)) {
                        unset($vars[0]);
                        $vars = array_values($vars);
                        foreach ($vars as $k => $v)
                        {
                            $this->model->setVars($arVars[$k][1], $v);
                        }
                        $actionParams = $params;
                        break 1;
                    }
                }
                elseif (preg_match("/^{$rule}$/", $paramsRow)) {
                    $actionParams = $params;
                    break;
                }

                $arDebugRules[$params['action']] = "[{$params['action']}] => {$rule}";
            }
        }

        return $actionParams;
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

        if (!is_null($data)) {
            $this->model->setData($data, true);
        }

        $templateResult = new TemplateResult(
            $this->componentName,
            $this->model->getData(),
            $this->model->getErrors(false),
            $this->model->getVars(),
            $this->model->debugInfo
        );

        return $templateResult->includeTemplate($templateName);
    }

    /**
     * Сохраняет переданный контент как результат работы компонента
     *
     * @param string $content
     * @param bool $overwrite
     */
    protected function putContent($content = '', $overwrite = false)
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
    protected function doAction($action = Model::ACTION_DEFAULT, $args = [])
    {
        $this->model->doAction($action, $args);
    }

    /**
     * По сути алиас для доступа к аналогичному методу модели компонента
     *
     * @param null $key
     * @param null $default
     *
     * @return mixed
     */
    protected function getVars($key = null, $default = null)
    {
        return $this->model->getVars($key, $default);
    }
}
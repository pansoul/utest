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
    const AJAX_MODE_JSON = 'json';
    const AJAX_MODE_HTML = 'html';

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
     * @var \UTest\Kernel\Component\Model $model
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
     * Маршрутизатор для компонентов.
     * 
     * Может принимать следующие ключи:
     * <ul>
     *      <li>
     *          redirect - url на который редиректить при определении текущего акшна.<br/>
     *          По умолчанию false
     *      </li>
     *      <li>
     *          title - какой заголовок страницы установить.<br/>
     *          По умолчанию null
     *      </li>
     *      <li>
     *          subtitle - какой подзаголовок страницы установить.<br/>
     *          Может быть анонимной функцией, принимающей один параметр - массив разобранных значений из строки параметров<br/>
     *          По умолчанию null
     *      </li>
     *      <li>
     *          action_main - какой акшн запускать, если строка параметров пуста.<br/>
     *          Другими словами, "корневой" запуск компонента.<br/>
     *          По умолчанию 'index'
     *      </li>
     *      <li>
     *          action_main - какой акшн запускать, если имеется строка параметров, но не удалось найти ни
     *          одного акшна для неё (поиск осуществляется в actions_params).<br/>
     *          По умолчанию null
     *      </li>
     *      <li>
     *          add_breadcrumb - добавлять ли заголовок в навигационную цепочку.<br/>
     *          По умолчанию false
     *      </li>
     *      <li>
     *          actions_params - Список строк параметров, определяющие акшн и др. настройки для них.<br/>
     *          По умолчанию array()<br/><br/>
     * 
     *          <b>Примечание!</b> Строка параметров - это часть url-строки, следующей после имени компонента.
     *          Если же компонет запускается вручную, то строка парметров всегда будет пустой, если только она
     *          не указана явно.<br/><br/>
     *
     *          Ключом должна являться описываемая строка параметров. А значением - настройки для этой строки,
     *          включающие в себя:<br/>
     *
     *          <ul>
     *              <li>action - укороченное имя акшна модели компонента</li>
     *              <li>redirect - соответствует описанию выше</li>
     *              <li>title - соответствует описанию выше</li>
     *              <li>subtitle - соответствует описанию выше</li>
     *              <li>add_breadcrumb - соответствует описанию выше</li>
     *          </ul>
     * 
     *          <b>Примечание!</b> Имена акшнов необходимо указывать без постфикса Action, который обязателен
     *          только в имени метода в модели компонента. Также разрешается запись с разделением имён акшна через
     *          нижнее подчёркивание - для таких акшнов запись метода в модели будет идентичной.
     *          Т.е. имена акшнов "editGroup" и "edit_group" будут иметь единый формат записи метода в модели
     *          компонента - editGroupAction().<br/><br/>
     * 
     *          Изначально парсер пробует найти текущую строку параметров из карты по полнотекстовому совпадению.
     *          Если строка не найдена, поиск осуществляется через регулярные выражения с раскрытием переменных.
     *          Переменные указываются в угловых скобках <имя_переменной>, имя переменной должно соответствовать
     *          правилу [-_a-zA-Z0-9]+.<br/>
     *          Если правило раскрытия переменной не указано в vars_rules, то переменная раскрывается
     *          по правилу [^\/]+.<br/>
     *          В конечно итоге раскрытая строка параметров подставляется в регулярное выражение /^раскрытая_строка$/<br/><br/>
     * 
     *          <b>Примечание!</b> Запускается всегда акшн из первой найденной строки параметров.<br/><br/>
     *          
     *          Пример:<br/>
     *          <pre>
     *          ...
     *          'actions_params' => [
     *              '/full/path' => [
     *                  'redirect' => '/'
     *              ],
     *              '/editgroup/<id>' => [
     *                  'action' => 'edit_group',
     *                  'title' => 'Редактирование группы',
     *                  'add_breadcrumb' => true,
     *              ],
     *              '/<group_code>' => [
     *                  'action' => 'student',
     *                  'title' => 'Cтуденты',
     *                  'subtitle' => function($vars){
     *                      return DB::table(TABLE)->where('alias', $vars['group_code'])->first()['title'];
     *                  }
     *              ],
     *              '/my/<subject_code>/test-<tid>/new' => [
     *                  'action' => 'my_new_question',
     *                  'title' => 'Создание нового вопроса',
     *                  'add_breadcrumb' => true
     *              ]
     *              ...
     *          ]
     *          ...
     *          </pre>
     *      </li>
     *      <li>
     *          vars_rules - список кастомных правил раскрытия переменных, определенных в строке параметров<br/>
     *          По умолчанию array()
     *          
     *          Ключом должно быть имя переменной, определённой в строке парметров в actions_params.
     *          А значением строка, выраженная частью регулярного выражения.
     *          
     *          пример:<br/>
     *          <pre>
     *          ...
     *          'vars_rules' => array(
     *              'type' => '[a-zA-Z]',
     *              'id' => '[0-9]'
     *              ...
     *          )
     *          ...
     *          </pre>     
     * </ul>
     *
     * @var array
     */
    protected $routeMap = array();
    
    /**
     * Маршрутизатор для компонентов по умолчанию
     * @var array
     */
    private $routeMapDefault = array(
        'redirect' => false,
        'title' => null,
        'subtitle' => null,
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
     * @param array $routeMap - маршрутизатор
     *
     * @return void
     */
    private function __construct($componentName = '', $action = false, $actionArgs = array(), $routeMap = array())
    {
        $this->componentName = $componentName;        
        $this->action = $action;
        $this->actionArgs = $actionArgs;
        $this->paramsRow = Site::getModParamsRow();

        if (!is_array($routeMap)) {
            $this->routeMap = $this->routeMapDefault;
        } else {
            $actionsParams = array_merge_recursive(
                $this->routeMapDefault['actions_params'],
                (array) @$this->routeMap()['actions_params'],
                (array) @$routeMap['actions_params']
            );
            $this->routeMap = array_merge($this->routeMapDefault, $this->routeMap(), (array) $routeMap);
            $this->routeMap['actions_params'] = $actionsParams;
        }
    }

    /**
     * Алиас для задания маршрутизатора компонента, но позволяющий указывать динамические ключи.
     * @return array
     */
    protected function routeMap()
    {
        return (array) $this->routeMap;
    }

    /**
     * Даёт команду "старт" работе компонента, выполняя требуемый акшн.<br/>
     * В этот момент весь компонент уже полностью собран.<br/>
     * Заполняет итоговый контент, выводимый пользователю.
     * @throws DoActionException
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
     * @param array|bool $routeMap - переопределение части или всего маршрутизатора загружаемого компонента.
     * Если маршрутизатор не нужен, то достаточно передать false.
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
    public static function includeComponentFiles($componentName)
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
        $isMainAction = $this->action === true || (!$this->paramsRow && !$this->action);
        $arDebugRules = [];
        $arVars = [];

        if ($isMainAction) {
            $this->action = $this->routeMap['action_main'];
            $actionParams = $this->routeMap;
        } else {
            $actionParams = $this->findActionParamsInRouteMap($this->paramsRow, $arDebugRules, $arVars);
            if ($actionParams['action']) {
                $this->action = $actionParams['action'];
            } elseif ($this->routeMap['action_default']) {
                $this->action = $this->routeMap['action_default'];
            }
        }

        if ($actionParams['redirect']) {
            Site::redirect($actionParams['redirect']);
        }

        $this->model = new $componentModel($this->componentName, $this->action);
        $this->model->setVars($arVars);

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
            $breadcrumbTitle = $this->routeMap['title'] ? $this->routeMap['title'] : self::DEFAULT_TITLE;
            $breadcrumbSubtitle = $this->getActionSubtitle($this->routeMap['subtitle']);
            if ($breadcrumbSubtitle) {
                $breadcrumbTitle .= ' :: ' . $breadcrumbSubtitle;
            }
            AppBuilder::addBreadcrumb($breadcrumbTitle, Site::getModUrl());
        }
        if (!AppBuilder::getTitle() || $actionParams['title']) {
            AppBuilder::setTitle($title);
        }
        if (!AppBuilder::getH() || $actionParams['title']) {
            AppBuilder::setH($title);
        }
        if ($actionParams['subtitle']) {
            AppBuilder::setSubtitle($this->getActionSubtitle($actionParams['subtitle']));
        }

        $paramsRowSplit = array_filter(explode('/', $this->paramsRow));
        $locParamsRow = '';
        foreach ($paramsRowSplit as $param) {
            $locParamsRow .= '/' . $param;
            $locActionParams = $this->findActionParamsInRouteMap($locParamsRow);
            if ($locActionParams) {
                if ($locActionParams['add_breadcrumb']) {
                    $breadcrumbTitle = $locActionParams['title'] ? $locActionParams['title'] : self::DEFAULT_TITLE;
                    $breadcrumbSubtitle = $this->getActionSubtitle($locActionParams['subtitle']);
                    if ($breadcrumbSubtitle) {
                        $breadcrumbTitle .= ' :: ' . $breadcrumbSubtitle;
                    }
                    AppBuilder::addBreadcrumb($breadcrumbTitle, Site::getModurl() . $locParamsRow);
                }
            }
        }
        
        if (!$this->action) {
            $this->putContent(Form::error("Action для строки параметров <b>{$this->paramsRow}</b> не найден"));
            $this->putContent(Form::error($arDebugRules));
            return;
        }

        try {
            $this->run();
        } catch (DoActionException $e) {
            $this->putContent(Form::error($e->getMessage() . " в компонете '{$this->componentName}'"));
        }
    }

    private function getActionSubtitle($subtitle = null)
    {
        if (is_callable($subtitle)) {
            $subtitle = $subtitle($this->model->getVars());
        }
        return $subtitle;
    }

    private function findActionParamsInRouteMap($paramsRow = '', &$arDebugRules = [], &$arVars = [])
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
                preg_match_all("/<([-_a-z0-9]+)>/i", $path, $arMatches, PREG_SET_ORDER);

                if ($arMatches) {
                    foreach ($arMatches as $match)
                    {
                        $rule = str_replace(
                            $match[0],
                            isset($this->routeMap['vars_rules'][$match[1]]) ? '('.$this->routeMap['vars_rules'][$match[1]].'+?)' : '([^\/]+?)',
                            $rule
                        );
                    }

                    if (preg_match("/^{$rule}$/", $paramsRow, $vars)) {
                        unset($vars[0]);
                        $vars = array_values($vars);
                        foreach ($vars as $k => $v)
                        {
                            $arVars[$arMatches[$k][1]] = $v;
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
            $this->action,
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
     * Возвращает контент для ajax-запроса.
     *
     * @param null $content - при null значении возьмётся весь текущий контент работы компонента
     * @param bool|string $ajaxOutputMode
     */
    protected function outputForAjax($content = null, $ajaxOutputMode = false)
    {
        if (is_null($content)) {
            $content = $ajaxOutputMode == self::AJAX_MODE_JSON ? $this->content : $this->getContent();
        }

        switch ($ajaxOutputMode) {
            case self::AJAX_MODE_JSON:
                header('Content-Type: application/json; charset=utf-8');
                $content = json_encode($content);
                break;

            case self::AJAX_MODE_HTML:
                header('Content-Type: text/html; charset=utf-8');
                break;

            default:
                header('Content-Type: text/plain; charset=utf-8');
                break;
        }

        echo $content;
        exit;
    }

    /**
     * Алиас для доступа к аналогичному методу модели компонента
     *
     * @param string $action
     * @param array $args
     *
     * @throws DoActionException
     */
    protected function doAction($action = Model::ACTION_DEFAULT, $args = [])
    {
        $this->model->doAction($action, $args);
    }

    /**
     * Алиас для доступа к аналогичному методу модели компонента
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

    /**
     * Алиас для доступа к методу getData() модели компонента
     * @return mixed
     */
    protected function getActionData()
    {
        return $this->model->getData();
    }
}
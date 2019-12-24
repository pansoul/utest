<?php

namespace UTest\Kernel;

class Base
{
    /**
     * Устанавливает "простой" режим работы.
     * При данном режиме нет проверки на авторизованность и отсутствует автозапуск
     * компонентов по адресной строке. Используется, чтобы просто подключить ядро
     * приложения, а далее выполнять свой код.
     * @var bool
     */
    private $simpleMode = false;

    /**
     * Сохраним массив настроек в переменную, чтобы в дальнейшем работать
     * с ним через классы
     * @var array
     */
    private static $config;

    public function __construct($simpleMode = false)
    {
        $this->simpleMode = (bool) $simpleMode;
    }

    /**
     * Инициализатор приложения
     * @param array $config
     */
    public function run(array $config)
    {
        @session_start();
        self::$config = $config;

        @set_exception_handler(['\\UTest\\Kernel\\Errors\\ExceptionHandler', 'exceptionHandler']);

        $this->setDebug();
        $this->setDbConnection();

        // @todo продумать над данным модом. Что должно быть прогружено, показано/не показано при нём?
        if ($this->simpleMode) {
            return;
        }

        $router = new AppRouter();
        $router->parse();
    }
    
    private function setDebug()
    {
        $debugConfig = self::getConfig('debug');
        
        if ($debugConfig['enable']) {
            if ($debugConfig['register_errors'] === 1) {
                error_reporting(E_ALL);
            } else {
                error_reporting($debugConfig['register_errors']);
            }
            ini_set('display_errors', $debugConfig['display_errors']);
            if (!$debugConfig['display_errors']) {
                ini_set("log_errors", 1);
                ini_set("error_log", $debugConfig['error_log']);
            }
        } else {
            error_reporting(0);
        }
    }

    private function setDbConnection()
    {
        $dbConfig = self::getConfig('db');
        $debugConfig = self::getConfig('debug');
        
        $capsule = new DB;
        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => $dbConfig['host'],
            'database' => $dbConfig['name'],
            'username' => $dbConfig['user'],
            'password' => $dbConfig['pass'],
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ]);
        $dispatcher = new \Illuminate\Events\Dispatcher;
        $dispatcher->listen(\Illuminate\Database\Events\StatementPrepared::class, function ($event) {
            $event->statement->setFetchMode(\PDO::FETCH_ASSOC);
        });

        if ($debugConfig['enable'] && $debugConfig['db_debug']) {
            $dispatcher->listen(\Illuminate\Database\Events\QueryExecuted::class, function($query){
                $q = [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time
                ];
                echo "<pre>"; var_dump($q); echo "</pre>";
            });
        }

        $capsule->setEventDispatcher($dispatcher);
        $capsule->setAsGlobal();
    }

    /**
     * Возвращает массив настроек приложения
     *
     * @param string|bool $path - какую ветвь из настроек вернуть.
     * Для указания вложенности между элементами используется знак ">".
     * Например, Base::getConfig('tpl_map > *') возвратит имя шаблона по умолчанию.
     *
     * @return boolean|array|string
     */
    public static function getConfig($path = false)
    {
        if (!$path) {
            return self::$config;
        }

        $value = self::$config;
        $arPath = explode('>', $path);
        $arPath = array_map('trim', $arPath);
        foreach ($arPath as $option) {
            if (!isset($value[$option])) {
                return null;
            }
            $value = $value[$option];
        }

        return $value;
    }
}
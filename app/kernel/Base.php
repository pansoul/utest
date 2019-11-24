<?php

namespace UTest\Kernel;

use \R;

class Base
{
    /**
     * Устанавливает "простой" режим работы.
     * При данном режиме нет проверки на авторизованность и отсутствует автозапуск
     * компонентов по адресной строке. Используется, чтобы просто подключить ядро
     * приложения, а далее выполнять свой код.
     * @var bool
     */
    protected $simpleMode;

    /**
     * Сохраним массив настроек в переменную, чтобы в дальнейшем работать
     * с ним через классы
     * @var array
     */
    protected static $config;

    public function __construct($simpleMode = false)
    {
        $this->simpleMode = (bool)$simpleMode;
    }

    /**
     * Инициализатор приложения
     * @param array $config
     */
    public function run(array $config)
    {
        @session_start();
        self::$config = $config;

        // Показывать ли различные предупреждения и ошибки. 
        // Регулируется через файл конфигурации
        if ($config['debug']['enable']) {
            if ($config['debug']['register_errors'] === 1) {
                error_reporting(E_ALL);
            } else {
                error_reporting($config['debug']['register_errors']);
            }
            ini_set('display_errors', $config['debug']['display_errors']);
            if (!$config['debug']['display_errors']) {
                ini_set("log_errors", 1);
                ini_set("error_log", $config['debug']['error_log']);
            }
        } else {
            error_reporting(0);
        }

        @set_exception_handler(['\\UTest\\Kernel\\Errors\\ExceptionHandler', 'exceptionHandler']);

        $dbconfig = $config['db'];
        R::setup(
            "mysql:host={$dbconfig['host']};port={$dbconfig['port']};dbname={$dbconfig['name']}",
            $dbconfig['user'],
            $dbconfig['pass']
        );
        R::freeze(true);
        if ($config['debug']['enable'] && $config['debug']['db_debug']) {
            R::fancyDebug(true);
            //R::debug(true);
        }

        if ($this->simpleMode) { // @todo продумать над данным модом. Что должно быть прогружено, показано/не показано при нём?
            return;
        }

        $router = new AppRouter();
        $router->parse();
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
                return false;
            }
            $value = $value[$option];
        }

        return $value;
    }
}
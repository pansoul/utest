<?php

class UBase {

    /**
     * Чтобы не произоводить ручное заполнения подключаемых классов для 
     * автозагрузчика, можно заполнить данный массив (осуществляется через 
     * функцию setCoreDir()). 
     * Данный массив содержит пути к каталогам, в которых лежат *.php файлы, 
     * все которые необходимо зарегистрировать для autoload.
     * @var array 
     */
    protected $arDir = array();
    
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
    
    /**
     * Для начала зарегистрируем классы, если не пуст массив self::$arDir и
     * @param type $simpleMode
     */
    public function __construct($simpleMode = false)
    {
        $this->simpleMode = (bool)$simpleMode;
        $this->setCoreDir();
        $this->loadDir($this->arDir);
    }

    /**
     * Инициализатор приложения
     * @param array $config
     */
    public function run($config)
    {
        self::$config = $config;

        $dbconfig = $config['db'];

        R::setup("mysql:host={$dbconfig['host']};port={$dbconfig['port']};dbname={$dbconfig['name']}", $dbconfig['user'], $dbconfig['pass']);
        R::freeze(true);
        if ($config['debug']['db_debug']) {
            R::debug();
        }           
        
        if ($this->simpleMode) return;

        $router = new URouter();
        $router->parse();
    }

    /**
     * Находит все *.php файлы в переданных каталогах для последующей регистраци
     * @param array|string $arPaths
     */
    public function loadDir($arPaths)
    {
        if (is_array($arPaths)) {
            foreach ($arPaths as $path)
            {
                $this->registerFromDir($path);
            }
        } elseif (is_dir($arPaths)) 
            $this->registerFromDir($arPaths);
    }

    /**
     * Возвращает массив настроек приложения
     * @param string|array $frond - какую ветвь из настроек вернуть
     * @return boolean|array|string
     * @throws Exception
     * 
     * @todo Переделать, чтобы ветвь можно было передавать в более интуитивно
     * понятной форме. К примеру, в виде "Feild1/Field1.1/../Field{N}"
     */
    public static function getConfig($frond)
    {
        if (!self::$config)
            return false;

        if (!$frond)
            return self::$config;
        else {
            if (is_array($frond)) {
                $_arr = self::$config;
                $isFind = true;
                foreach ($frond as $v)
                {
                    $_arr = $_arr[$v];
                    if (isset($_arr))
                        continue;
                    else {
                        $isFind = false;
                        break;
                    }
                }
                if ($isFind)
                    return $_arr;
            } elseif (is_string($frond) && isset(self::$config[$frond]))
                return self::$config[$frond];

            //throw new Exception('Данная ветвь не найдена в файле настроек! Проверьте правильность написания.');
            return false;
        }
    }

    /**
     * Регистрация классов в autoload.
     * Внимание! Наименование файлов должно в точности совпадать с именованием класса.
     * @global object $LOADER
     * @param string $path
     */
    protected function registerFromDir($path)
    {
        global $LOADER;
        $_class = array();

        foreach (glob($path . '/*.php') as $filename)
        {
            $info = pathinfo($filename);
            $classname = ucfirst($info['filename']);

            $_class[$classname] = $filename;
            $LOADER->addClassMap($_class);
            $LOADER->register(true);
        }
    }

    /**
     * Регистрирует функцию по вылавливанию исключений
     * @param object $exception
     * @return void
     */
    public static function exceptionHandler($exception)
    {
        if (!error_reporting())
            return;
        
        if (self::$config['debug']['display_errors']) {                 
            echo USiteErrors::exception($exception->getMessage(), $exception->getFile(), $exception->getLine(), $exception->getTrace());
            return;
        }

        // ну а иначе записываем в лог-файл
        
        // these are our templates
        $traceline = "#%s %s(%s): %s(%s)";
        $msg = "PHP Fatal error:  Uncaught exception '%s' with message '%s' in %s:%s\nStack trace:\n%s\n  thrown in %s on line %s";

        // alter your trace as you please, here
        $trace = $exception->getTrace();        
        
        foreach ($trace as $key => $stackPoint)
        {
            // I'm converting arguments to their type
            // (prevents passwords from ever getting logged as anything other than 'string')
            $trace[$key]['args'] = array_map('gettype', $trace[$key]['args']);
        }

        // build your tracelines
        $result = array();
        foreach ($trace as $key => $stackPoint)
        {
            $result[] = sprintf(
                    $traceline, 
                    $key, 
                    $stackPoint['file'], 
                    $stackPoint['line'], 
                    $stackPoint['function'], 
                    implode(', ', $stackPoint['args'])
            );
        }
        // trace always ends with {main}
        $result[] = '#' . ++$key . ' {main}';

        // write tracelines into main template
        $msg = sprintf(
                $msg, 
                get_class($exception), 
                $exception->getMessage(), 
                $exception->getFile(), 
                $exception->getLine(), 
                implode("\n", $result), 
                $exception->getFile(), 
                $exception->getLine()
        );

        // log or echo as you please
        error_log($msg);
    }

    /**
     * Заполняем массив автоподключаемых каталогов
     */
    protected function setCoreDir()
    {
        $this->arDir = array(
            KERNEL_PATH,
            KERNEL_PATH . '/form',
            KERNEL_PATH . '/test',
            KERNEL_PATH . '/test/types',
            KERNEL_PATH . '/user',
            KERNEL_PATH . '/user/roles',
            KERNEL_PATH . '/errors'
        );
    }

}

set_exception_handler(array('UBase', 'exceptionHandler'));

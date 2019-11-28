<?php

/**
 * Определим переменную корня нашего приложения
 */
define('ROOT', realpath('.'));

/**
 * Определим переменную системного каталога приложения
 */
define('APP_PATH', ROOT . '/app');

/**
 * Путь к темам приложения
 */
define('THEMES_PATH', ROOT . '/themes');

/**
 * Путь, где находится файл настроек приложения
 */
define('APP_CONFIG_PATH', __DIR__);

/**
 * Путь ядра приложения
 */
define('KERNEL_PATH', APP_PATH . '/kernel');

/**
 * Путь к компонентам
 */
define('COMPONENTS_PATH', APP_PATH . '/components');

// Автозагрузчик классов
require_once APP_PATH . '/composer/vendor/autoload.php';

// Таблицы
define('TABLE_USER', 'u_user');
define('TABLE_USER_ROLES', 'u_user_roles');
define('TABLE_UNIVER_DATA', 'u_univer_data');
define('TABLE_UNIVER_FACULTY', 'u_univer_faculty');
define('TABLE_UNIVER_GROUP', 'u_univer_group');
define('TABLE_UNIVER_SPECIALITY', 'u_univer_speciality');
define('TABLE_PREPOD_MATERIAL', 'u_prepod_material');
define('TABLE_PREPOD_SUBJECT', 'u_prepod_subject');
define('TABLE_STUDENT_MATERIAL', 'u_student_material');
define('TABLE_STUDENT_TEST', 'u_student_test');
define('TABLE_STUDENT_TEST_ANSWER', 'u_student_test_answer');
define('TABLE_STUDENT_TEST_PASSAGE', 'u_student_test_passage');
define('TABLE_STUDENT_TEST_TIME', 'u_student_test_time');
define('TABLE_TEST', 'u_test');
define('TABLE_TEST_ANSWER', 'u_test_answer');
define('TABLE_TEST_QUESTION', 'u_test_question');

define('ERROR_ELEMENT_NOT_FOUND', 'e404');
define('LAYOUT_404', 404);

/**
 * Возвращаемый массив настроек и параметров для работы приложения
 */
return array(
    // Подключение к БД
    'db' => array(
        'host' => $_SERVER['REMOTE_ADDR'], // адрес MySQL хоста
        'port' => '', // номер порта для подключения
        'user' => 'root', // имя пользователя БД
        'pass' => '', // пароль пользователя БД
        'name' => 'utest' // название БД
    ),

    // Отладка приложения
    // Функционал рекомендован чисто для разработки
    'debug' => array(
        // Активность режима отладки в целом
        'enable' => true,

        // Режим отладки при работе с БД.
        // При включенном состоянии показывает диагностические сообщения работы sql        
        'db_debug' => false,

        // Режим отладки при работе с компонентами.
        // При включенном режиме показывает диагностическую информацию о каждом 
        // подключенном компоненте на странице
        'component_debug' => false,

        // Протоколирования ошибок. Содержит битовую маску или именованные константы ошибок.
        // При указании значения 1 будут включены все PHP ошибки.
        'register_errors' => E_ALL ^ E_NOTICE ^ E_WARNING ^ E_STRICT,
        
        // Отображать ли сообщения об ошибках на экран или записывать их в файл
        'display_errors' => true,

        // Куда будут сохраняться сообщения об ошибках  
        'error_log' => __DIR__ . '/php_error.log'
    ),

    // Здесь определяем какую тему применить
    'theme' => 'utest',

    // Также можно настроить "карту" применяемых шаблонов для страниц.
    // "Карта" настраивается только для url компонентов, если необходимо настроить
    // шаблон для произвольного url-адреса, то прежде его нужно зарегистрировать
    // в списке URL алиасов!
    // Внимание! При настройке "карты" нужно помнить, что элемент с ключом 
    // '*' (звёздочка) является обязательным - считается шаблоном по умолчанию!
    'tpl_map' => array(
        '*' => 'inner',
        '/' => 'main',
        // можно продолжать список, определяя для каждого url-адреса свой шаблон
        // к примеру запись: 
        // ...
        // '/root/example' => 'my_template'
        // ...
        // будет означать, что при запросе страницы "/root/example" будет подгружен
        // шаблон "my_template" из выбранной выше темы <theme>
    ),

    // Список URL алиасов
    // Ключом должен быть путь страницы от корня сайта (пример: "/root/example")
    // Каждый алиас должен быть описан массивом, состоящий из 2-х элементов:
    // 1-й: имя компонента (обязательный). Можно также установить в false, если необходимо просто закрепить алиас для пользовательского шаблона
    // 2-й: доступ только авторизованным пользователям или всем (необязательный, по умолчанию true - только авторизованным)
    'url_aliases' => array(
        // ...
        //'/test' => array('test', false)
        // ...
        // данный пример будет означать, что при запросе страницы "/test" будет
        // загружен компонент "test", при этом страница будет доступна всем
        // 
        // Внимание! Переопределение системных алиасов на пользовательские или же
        // запуск компонетов, зависящих от типа пользователя (авторизации),
        // на пользовательские алиасы может иметь непредсказуемый результат!
    ),

    // Список меню для каждого типа (роли) пользователя
    'menus' => require 'menus.php',

    // Список ролей пользователей
    'roles' => require 'roles.php',

    // Список должностей
    'positions' => require 'positions.php'
);


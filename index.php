<?php
session_start();

// Подключаем главный конфигурационный файл
$config = require __DIR__ . '/app/config/main.php';

// Показывать ли различные предупреждения и ошибки. 
// Регулируется через файл конфигурации
if ($config['debug']['enable']) {
    if ($config['debug']['register_errors_all'])
        error_reporting(E_ALL);
    else
        error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
    ini_set('display_errors', $config['debug']['display_errors']);
    if (!$config['debug']['display_errors']) {
        ini_set("log_errors", 1);
        ini_set("error_log", $config['debug']['error_log']);
    }
} else {
    error_reporting(0);
}

$webapp = new UBase();
$webapp->run($config);
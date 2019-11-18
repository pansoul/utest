<?php
// Подключаем главный конфигурационный файл
$config = require __DIR__ . '/app/config/main.php';

$webapp = new UTest\Kernel\Base();
$webapp->run($config);
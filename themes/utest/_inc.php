<?php

/**
 * очень полезный файл, если нужно добавить какую-нибудь информацию в шаблон, 
 * используя шаблонную переменную ([!<имя_переменной>]).
 * 
 * Информация может быть как статичной, так быть представлена фрагментом php-кода.
 * К примеру, номер телефона или копирайт, или вызов какого-либо компонента.
 * 
 * Также, можно переопределить вывод таких системных шаблонных переменных 
 * как: [!menu], [!title], [!h] и [!content]; однако при этом, информация визуально 
 * изменится, но программно всё будет дееспособным. Это нужно помнить!
 * 
 * Порядок переопределения: локальные заменяют общие, при условии, что 
 * используется данный шаблон.
 * 
 * ##
 * 
 * Пример:
 * 
 * return array(
 *     // переменные без принадлежности к шаблону являются общие
 *     'copyright' => '(c) 2013'
 *     ...
 *     // После указания имени шаблона, все переменные, объвяленные в нём, будут локальными
 *     'inner' => array(
 *          ...
 *          // При открытии страницы с данным шаблоном "inner" в переменной 
 *          // [!copyright] будет значение, указаное ниже
 *          'copyright' => '(c) Все права защищены'
 *          ...
 *      )
 * )
 * 
 */
return array(    
    'panel' => "<? return USiteController::loadComponent('utility', 'panel'); ?>",
    'univer_name' => "<? return USiteController::loadComponent('utility', 'univer', array('univer_name')); ?>",
    'univer_fullname' => "<? return USiteController::loadComponent('utility', 'univer', array('univer_fullname')); ?>",
    'univer_address' => "<? return USiteController::loadComponent('utility', 'univer', array('address')); ?>",
    'univer_phone' => "<? return USiteController::loadComponent('utility', 'univer', array('phone')); ?>",
    'breadcrump' => "<? return USiteController::loadComponent('utility', 'breadcrumb', array(UAppBuilder::getBreadcrumb())); ?>",
    'copyright' => '2013. Боровских Павел Сергеевич'
);
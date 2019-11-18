<?php

use UTest\Kernel\ComponentController;

/**
 * Очень полезный файл, если нужно добавить какую-нибудь информацию в шаблон,
 * используя шаблонную переменную ([!имя_переменной]).
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
    'panel' => ComponentController::loadComponent('utility', 'panel'),
    'univer_name' => ComponentController::loadComponent('utility', 'univer', array('univer_name')),
    'univer_fullname' => ComponentController::loadComponent('utility', 'univer', array('univer_fullname')),
    'univer_address' => ComponentController::loadComponent('utility', 'univer', array('address')),
    'univer_phone' => ComponentController::loadComponent('utility', 'univer', array('phone')),
    'breadcrump' => ComponentController::loadComponent('utility', 'breadcrumb', array(UTest\Kernel\AppBuilder::getBreadcrumb())),
    'copyright' => '2019. Боровских Павел Сергеевич'
);
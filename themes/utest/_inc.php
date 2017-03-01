<?php

/**
 * очень полезный файл, если нужно добавить какую-нибудь информацию в шаблон, 
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
    'panel' => USiteController::loadComponent('utility', 'panel'),
    'univer_name' => USiteController::loadComponent('utility', 'univer', array('univer_name')),
    'univer_fullname' => USiteController::loadComponent('utility', 'univer', array('univer_fullname')),
    'univer_address' => USiteController::loadComponent('utility', 'univer', array('address')),
    'univer_phone' => USiteController::loadComponent('utility', 'univer', array('phone')),
    'breadcrump' => USiteController::loadComponent('utility', 'breadcrumb', array(UAppBuilder::getBreadcrumb())),
    'copyright' => '2013. Боровских Павел Сергеевич'
);
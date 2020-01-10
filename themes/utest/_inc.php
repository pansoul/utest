<?php

use UTest\Kernel\Component\Controller;

/**
 * Очень полезный файл, если нужно добавить какую-нибудь информацию в шаблон,
 * используя шаблонную переменную ([!имя_переменной]).
 * 
 * Также, можно переопределить вывод таких системных шаблонных переменных 
 * как: [!menu], [!title], [!h], [!content], [!theme_url] и [!delayed_js]; однако при этом, информация визуально
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
    'panel' => Controller::loadComponent('utility', 'panel'),
    'univer_name' => Controller::loadComponent('utility', 'univer', array('name')),
    'univer_fullname' => Controller::loadComponent('utility', 'univer', array('fullname')),
    'univer_address' => Controller::loadComponent('utility', 'univer', array('address')),
    'univer_phone' => Controller::loadComponent('utility', 'univer', array('phone')),
    'breadcrump' => Controller::loadComponent('utility', 'breadcrumb', array(UTest\Kernel\AppBuilder::getBreadcrumb())),
    'copyright' => '2019. Боровских Павел Сергеевич'
);
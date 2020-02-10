<?php

use UTest\Kernel\Component\Controller;

/**
 * Очень полезный файл, если нужно добавить какую-нибудь информацию в шаблон,
 * используя шаблонную переменную ([!имя_переменной]).
 * 
 * Также, можно переопределить вывод таких системных шаблонных переменных 
 * как: [!menu], [!title], [!h], [!content], [!theme_url] и [!scripts]; однако при этом, информация визуально
 * изменится, но программно всё будет дееспособным. Это нужно помнить! // @todo Запретить переопределение сис. переменных
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
    'univer_name' => Controller::loadComponent('admin.setting', 'univer', array('name'), false),
    'univer_fullname' => Controller::loadComponent('admin.setting', 'univer', array('fullname'), false),
    'univer_address' => Controller::loadComponent('admin.setting', 'univer', array('address'), false),
    'univer_phone' => Controller::loadComponent('admin.setting', 'univer', array('phone'), false),
    'breadcrump' => Controller::loadComponent('utility', 'breadcrumb', array(UTest\Kernel\AppBuilder::getBreadcrumb())),
    'copyright' => '2019. Боровских Павел Сергеевич',
    'subtitle' => function($subtitle){
        return !empty($subtitle) ? " <span class='subtitle'><span class='subtitle__separator'>::</span> {$subtitle}</span>" : '';
    }
);
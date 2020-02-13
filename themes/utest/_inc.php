<?php

use UTest\Kernel\Component\Controller;

/**
 * Очень полезный файл, если нужно добавить какую-нибудь динамическую или повторяющуюся информацию в шаблон.
 * Для этого необходимо использовать шаблонную переменную ([!<имя_переменной>]).
 * 
 * Существует ряд системных шаблонных переменных:
 * [!menu], [!title], [!h], [!content], [!theme_url], [!scripts], [!subtitle].
 * Их переопределение не имеет смысла, т.к. код, сохраняющий данные в эти переменные, выполнится в любом случае.
 * Однако, можно кастомизировать выводимую информацию из системных переменных. Для этого достаточно назначить значение
 * в виде анонимной функции, принимающей один параметр - контен переменной.
 * 
 * ##
 * 
 * Пример:
 * 
 * return array(
 *      ...
 *      // Указание статической информации
 *      'copyright' => '(c) 2013'
 *
 *      // Указание динамической информации
 *      'name' => UTest\Kernel\Component\Controller::loadComponent(...);
 *
 *      // Кастомизация контента системной переменной
 *      'h' => function($content) {
            return 'UTest - ' . $content;
 *      },
 *      ...
 * );
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
    'subtitle' => function($content){
        return !empty($content) ? " <span class='subtitle'><span class='subtitle__separator'>::</span> {$content}</span>" : '';
    }
);
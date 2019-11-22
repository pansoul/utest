<?php

namespace UTest\Components;

class AdminSettingController extends \UTest\Kernel\Component\Controller
{
    protected $routeMap = array(
        'title' => 'Информация о вузе',
        'add_breadcrumb' => true,
    );
}
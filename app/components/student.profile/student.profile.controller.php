<?php

namespace UTest\Components;

class StudentProfileController extends \UTest\Kernel\Component\Controller
{
    protected $routeMap = array(
        'title' => 'Личные данные',
        'add_breadcrumb' => true
    );
}
<?php

namespace UTest\Components;

class AdminController extends \UTest\Kernel\ComponentController
{
    protected $routeMap = array(
        'setTitle' => 'Как пользоваться системой?',
        'addBreadcrumb' => false
    );
}
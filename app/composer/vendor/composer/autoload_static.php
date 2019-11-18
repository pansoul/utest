<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit882d5cc1882b6a850926aca8c0e7bbb6
{
    public static $files = array (
        '0e6d7bf4a5811bfa5cf40c5ccd6fae6a' => __DIR__ . '/..' . '/symfony/polyfill-mbstring/bootstrap.php',
        '25072dd6e2470089de65ae7bf11d3109' => __DIR__ . '/..' . '/symfony/polyfill-php72/bootstrap.php',
        '667aeda72477189d0494fecd327c3641' => __DIR__ . '/..' . '/symfony/var-dumper/Resources/functions/dump.php',
        'd41f4bfceb60cb8d534df7c2f4f1b7a6' => __DIR__ . '/../..' . '/../kernel/db/rb_v4.3.2.php',
    );

    public static $prefixLengthsPsr4 = array (
        'U' => 
        array (
            'UTest\\' => 6,
        ),
        'S' => 
        array (
            'Symfony\\Polyfill\\Php72\\' => 23,
            'Symfony\\Polyfill\\Mbstring\\' => 26,
            'Symfony\\Component\\VarDumper\\' => 28,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'UTest\\' => 
        array (
            0 => __DIR__ . '/../..' . '/..',
        ),
        'Symfony\\Polyfill\\Php72\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-php72',
        ),
        'Symfony\\Polyfill\\Mbstring\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-mbstring',
        ),
        'Symfony\\Component\\VarDumper\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/var-dumper',
        ),
    );

    public static $classMap = array (
        'Verot\\Upload\\Upload' => __DIR__ . '/..' . '/verot/class.upload.php/src/class.upload.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit882d5cc1882b6a850926aca8c0e7bbb6::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit882d5cc1882b6a850926aca8c0e7bbb6::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit882d5cc1882b6a850926aca8c0e7bbb6::$classMap;

        }, null, ClassLoader::class);
    }
}

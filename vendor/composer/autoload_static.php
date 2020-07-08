<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit13e61e46616a730a839e8a99997ea2f4
{
    public static $prefixLengthsPsr4 = array (
        'M' => 
        array (
            'MBBParser\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'MBBParser\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit13e61e46616a730a839e8a99997ea2f4::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit13e61e46616a730a839e8a99997ea2f4::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}

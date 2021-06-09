<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitbf15e7c94539a13d4781ec2fb486b72f
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PHPMailer\\PHPMailer\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PHPMailer\\PHPMailer\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpmailer/phpmailer/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitbf15e7c94539a13d4781ec2fb486b72f::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitbf15e7c94539a13d4781ec2fb486b72f::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
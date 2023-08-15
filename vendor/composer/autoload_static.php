<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit5c142c54840569e846ab2099844351aa
{
    public static $prefixLengthsPsr4 = array (
        'R' => 
        array (
            'Root\\Psmodcpf\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Root\\Psmodcpf\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit5c142c54840569e846ab2099844351aa::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit5c142c54840569e846ab2099844351aa::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit5c142c54840569e846ab2099844351aa::$classMap;

        }, null, ClassLoader::class);
    }
}
<?php
namespace Ed;

class Base extends \Http\Resource
{
    public static $base;
    public static $viewsDir;

    public static function setup($base = '')
    {
        static::$viewsDir = __DIR__ . '/../views/';
        static::$base = $base;
        return array(
            'Ed\AssetsResource',
            'Ed\TableResource',
            'Ed\ColumnResource',
        );
    }
}

<?php
namespace Ed;

class Base extends \Http\Resource
{
    public static $base;

    public static function setup($base = '')
    {
        static::$base = $base;
        return array(
            'Ed\AssetsResource',
            'Ed\TableResource',
            'Ed\ColumnResource',
        );
    }
}

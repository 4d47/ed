<?php
namespace Ed;

class Base extends \Http\Resource
{
    public static $base;
    public static $viewsDir;

    protected $model;

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

    public function __construct(Model $model)
    {
        $this->model = $model;
        if (!$this->model->checkBasicAuth(array_get($_SERVER, 'PHP_AUTH_USER'), array_get($_SERVER, 'PHP_AUTH_PW'))) {
            header('WWW-Authenticate: Basic realm="Admin"');
            throw new \Http\Unauthorized();
        }
    }

}

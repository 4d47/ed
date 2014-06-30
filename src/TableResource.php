<?php
namespace Ed;

class TableResource extends Base
{
    public static $path = '/(:table(/:key)(.:extension))';
    public $table;
    public $key;
    public $extension;
    private $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function init()
    {
        if (empty($this->table)) {
            throw new \Http\SeeOther(static::link(array('table' => current($this->model->getTables()))));
        }
    }

    public function get()
    {
        $result = $this->model->get($this->table, $this->key, $_GET) ?: self::notFound();
        $result->flash = static::flash('info');
        return $result;
    }

    public function post()
    {
        if ($this->key === 'new') {
            $this->key = $this->model->create($this->table, $_POST, $_FILES);
            static::flash('info', "$this->table was successfully created.");
        } else {
            $this->model->update($this->table, $this->key, $_POST, $_FILES);
            static::flash('info', "$this->table was successfully updated.");
        }
        throw new \Http\SeeOther(static::link(array('table' => $this->table, 'key' => $this->key)));
    }

    public function delete()
    {
        if (empty($this->key))
            throw new \Http\NotImplemented();
        $this->model->delete($this->table, $this->key);
        static::flash('info', "$this->table was successfully deleted.");
        throw new \Http\SeeOther(static::link(array('table' => $this->table)));
    }

    /**
     * Record $name $message to the session and get it back,
     * only once. Requires a session_start()ed.
     *
     * @param string $name
     * @param mixed $message
     */
    protected static function flash($name, $message = null)
    {
        if (is_null($message)) {
            $message = isset($_SESSION[$name]) ? $_SESSION[$name] : '';
            unset($_SESSION[$name]);
            return $message;
        }
        return $_SESSION[$name] = $message;
    }

    /**
     * Override to support multiple formats.
     */
    public function render($data)
    {
        switch($this->extension) {
        case '':
            parent::render($data);
            break;
        case 'html':
            throw new \Http\MovedPermanently(static::link(array('table' => $this->table, 'key' => $this->key)));
        case 'json':
            header('Content-Type: application/json');
            unset($data->table, $data->key, $data->schema, $data->config);
            $options = defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 0;
            echo json_encode($data, $options);
            break;
        case 'data':
            header('Content-Type: text/plain');
            unset($data->config);
            print_r($data);
            break;
        default:
            self::notFound();
        }
    }
    
    private static function notFound()
    {
        throw new \Http\NotFound();
    }
}

<?php
namespace Scrudler;

class ScrudlerResource extends \Http\Resource
{
    public static $path = '/?(?P<table>[^/.]+)?/?(?P<key>[^/.]+)?(?P<extension>\.(html|json|data))?';
    public static $layout = false;
    public $table, $key, $extension;
    private $db;

    public function __construct(\Scrudler\Scrudler $db)
    {
        $this->db = $db;
    }

    public function init()
    {
        if (empty($this->table)) {
            throw new \Http\SeeOther(static::link(current($this->db->getTables())));
        }
    }

    public function get()
    {
        $result = $this->db->get($this->table, $this->key, $_GET) ?: $this->raise('Http\NotFound');
        $result->flash = static::flash('info');
        return $result;
    }

    public function post()
    {
        if ($this->key === 'new') {
            $this->key = $this->db->create($this->table, $_POST);
            static::flash('info', "$this->table was successfully created.");
        } else {
            $this->db->update($this->table, $this->key, $_POST);
            static::flash('info', "$this->table was successfully updated.");
        }
        throw new \Http\SeeOther(static::link($this->table, $this->key));
    }

    public function delete()
    {
        if (empty($this->key))
            throw new \Http\NotImplemented();
        $this->db->delete($this->table, $this->key);
        static::flash('info', "$this->table was successfully deleted.");
        throw new \Http\SeeOther(static::link($this->table));
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
    protected static function render($resource, $data)
    {
        switch($resource->extension) {
        case '.html':
            throw new \Http\MovedPermanently(static::link($resource->table, $resource->key));
        case '.json':
            header('Content-Type: application/json');
            unset($data->table, $data->key, $data->schema);
            $options = defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 0;
            echo json_encode($data, $options);
            break;
        case '.data':
            header('Content-Type: text/plain');
            print_r($data);
            break;
        default:
            parent::render($resource, $data);
            break;
        }
    }
}

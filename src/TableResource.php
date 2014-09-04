<?php
namespace Ed;

class TableResource extends Base
{
    public static $path = '/(:table(/:id)(.:extension))';
    public $table;
    public $id;
    public $extension;
    public $data;
    private $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function init()
    {
        if (empty($this->table)) {
            throw new \Http\SeeOther(static::link(current($this->model->getTables())));
        }
    }

    public function get()
    {
        $this->data = $this->model->get($this->table, $this->id, $_GET);
        if (empty($this->data)) {
            throw new \Http\NotFound();
        }
        $this->data->flash = static::flash('info');
    }

    public function post()
    {
        if ($this->id === 'new') {
            $this->id = $this->model->create($this->table, $_POST, $_FILES);
            static::flash('info', sprintf(_("%s was successfully created."), $this->table));
        } else {
            $this->model->update($this->table, $this->id, $_POST, $_FILES);
            static::flash('info', sprintf(_("%s was successfully updated."), $this->table));
        }
        throw new \Http\SeeOther(static::link($this->table, $this->id));
    }

    public function delete()
    {
        if (empty($this->id))
            throw new \Http\NotImplemented();
        $this->model->delete($this->table, $this->id);
        static::flash('info', sprintf(_("%s was successfully deleted."), $this->table));
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
     * Overriden to support multiple formats.
     */
    public function render()
    {
        switch($this->extension) {
        case '':
            parent::render();
            break;
        case 'html':
            throw new \Http\MovedPermanently(static::link($this->table, $this->id));
        case 'json':
            header('Content-Type: application/json');
            unset($this->data->table, $this->data->id, $this->data->schema, $this->data->config);
            $options = defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 0;
            echo json_encode($this->data, $options);
            break;
        case 'data':
            header('Content-Type: text/plain');
            unset($this->data->config);
            print_r($this->data);
            break;
        default:
            throw new \Http\NotFound();
        }
    }
}

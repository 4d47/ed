<?php
namespace Ed;

class ColumnResource extends Base
{
    public static $path = '/:table/:id/:column';
    public $table;
    public $id;
    public $column;
    public $data;
    private $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function get()
    {
        $this->data = $this->model->fetchColumn($this->table, $this->id, $this->column);
        if (empty($this->data)) {
            throw new \Http\NotFound();
        }
    }

    public function render()
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        header('Content-Type: ' . finfo_buffer($finfo, $this->data));
        header('Content-Length: ' . strlen($this->data));
        finfo_close($finfo);
        echo $this->data;
    }
}

<?php
namespace Ed;

class ColumnResource extends Base
{
    public static $path = '/:table/:id/:column';
    public $table;
    public $id;
    public $column;
    private $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function get()
    {
        return $this->model->fetchColumn($this->table, $this->id, $this->column);
    }

    public function render($data)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        header('Content-Type: ' . finfo_buffer($finfo, $data));
        header('Content-Length: ' . strlen($data));
        finfo_close($finfo);
        echo $data;
    }
}

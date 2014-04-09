<?php
namespace Scrudler;

class BlobResource extends \Http\Resource
{
    public static $path = '/:table/:id/:column';
    public $table;
    public $id;
    public $column;
    private $db;

    public function __construct(\Scrudler\Scrudler $db)
    {
        $this->db = $db;
    }

    public function get()
    {
        return $this->db->fetchColumn($this->table, $this->id, $this->column);
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

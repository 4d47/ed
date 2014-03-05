<?php
namespace Scrudler;

class AttachmentResource extends \Http\Resource
{
    public static $path = '/:table/:id/:attachment';
    public $table;
    public $id;
    public $attachment;
    private $db;

    public function __construct(\Scrudler\Scrudler $db)
    {
        $this->db = $db;
    }

    public function get()
    {
        $filename = $this->db->getAttachment($this->table, $this->id, $this->attachment);
        if (!$filename) {
            throw new \Http\NotFound();
        }
        $data = array(
            'filename' => $filename,
            'lastModified' => filemtime($filename)
        );
        return $data;
    }

    protected static function render($resource, $data)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        header('Content-Type: ' . finfo_file($finfo, $data['filename']));
        header('Content-Length: ' . filesize($data['filename']));
        finfo_close($finfo);
        readfile($data['filename']);
    }
}

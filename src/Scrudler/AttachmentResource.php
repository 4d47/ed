<?php
namespace Scrudler;

class AttachmentResource extends \Http\Resource
{
    public static $path = '/:table/:id/:attachment';
    public static $layout = false;
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
        return $filename;
    }

    /**
     * Override to support multiple formats.
     */
    protected static function render($resource, $filename)
    {
        if (self::cachingHeaders($filename)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            header('Content-Type: ' . finfo_file($finfo, $filename));
            header('Content-Length: ' . filesize($filename));
            finfo_close($finfo);
            readfile($filename);
        }
    }

    /**
     * @return boolean If the browser does not have a copy
     */
    private static function cachingHeaders($filename)
    {
        $timestamp = filemtime($filename);
        $gmt_mtime = gmdate('r', $timestamp);
        header('ETag: "' . md5($timestamp . $filename) . '"');
        header("Last-Modified: $gmt_mtime");
        header("Cache-Control: public");

        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            if ($_SERVER['HTTP_IF_MODIFIED_SINCE'] == $gmt_mtime || str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == md5($timestamp . $filename)) {
                header('HTTP/1.1 304 Not Modified');
                return false;
            }
        }
        return true;
    }
}

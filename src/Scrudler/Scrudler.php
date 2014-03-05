<?php
namespace Scrudler;

use Stringy\StaticStringy as S;

class Scrudler
{
    private $db;
    private $schema;
    private $selectFilter;
    private $maxPerPage;
    private $attachments;
    private $attachmentsDirectory;

    public function __construct(\PDO2 $pdo)
    {
        global $config;
        $this->db = $pdo;
        $this->schema = $config['schema_filter']( \DbIntrospector::introspect($this->db->pdo, $config['db']['tag']) );
        $this->selectFilter = $config['select_filter'];
        $this->maxPerPage = $config['max_per_page'];
        $this->attachments = array_merge(array_map(function() { return array(); }, $this->schema), $config['attachments']);
        $this->attachmentsDirectory = $config['attachments_directory'];
    }

    public function getTables()
    {
        $tables = array_keys($this->schema);
        sort($tables);
        return $tables;
    }

    public function getPrimaryKey($table)
    {
        foreach ($this->schema[$table] as $name => $column) {
            if (!empty($column['pk']))
                return $name;
        }
        throw new \Exception("$table table must have a primary key");
    }

    public function getAttachments($table)
    {
        return empty($this->attachments[$table]) ? array() : $this->attachments[$table];
    }

    public function getAttachment($table, $id, $name)
    {
        return current(glob($this->attachmentsDirectory . DIRECTORY_SEPARATOR . $table . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . $name . '.*'));
    }

    public function get($table, $key = null, array $filters = array())
    {
        // this is what we will return to the page
        $data = (object) array(
            'table' => $table,
            'key' => $key,
            'row' => null,
            'attachments' => array(),
            'has' => array(),
            'schema' => $this->schema,
        );
        if (empty($this->schema[$table])) {
            return null;
        }
        if (empty($key)) {
            $data->has[$table] = $this->fetchPage($table, array(), $filters);
        }
        if ($key && $key != 'new') {
            // fetching a specific element identified by key
            $where = call_user_func($this->selectFilter, $table, array());
            $where[$this->getPrimaryKey($table)] = $key;
            if (! $data->row = $this->db->select($table, $where)->fetch()) {
                return null;
            }
            $this->addToString($table, $data->row);
            // fetch object has
            foreach ($this->schema as $tbl => $cols) {
                foreach ($cols as $name => $col) {
                    if (!empty($col['ref']) && $col['ref']['table'] === $table) {
                        $data->has[$tbl] = $this->fetchPage($tbl, array($name => $data->row->{$col['ref']['column']}), $filters);
                    }
                }
            }
            // add attachments
            foreach ($this->attachments[$table] as $name => $extensions) {
                $data->attachments[$name] = array(
                    'available' => (bool) $this->getAttachment($table, $key, $name),
                    'extensions' => $extensions
                );
            }
        }
        return $data;
    }

    public function create($table, $data, $attachments = array())
    {
        $data = $this->filterValues($table, $data);
        $this->db->insert($table, $data);
        $key = $this->db->lastInsertId();
        if ($key && $attachments) {
            $this->attach($table, $key, $attachments);
        }
        return $key;
    }

    public function delete($table, $key)
    {
        $where = array();
        $where[$this->getPrimaryKey($table)] = $key;
        $this->db->delete($table, $where);
        foreach ($this->getAttachments($table) as $name => $allowed) {

            $basename = $this->attachmentsDirectory . DIRECTORY_SEPARATOR . $table . DIRECTORY_SEPARATOR . $key . DIRECTORY_SEPARATOR . $name;
            foreach ($allowed as $mimetype => $extension) {
                $filename = $basename . $extension;
                if (file_exists($filename)) {
                    unlink($basename . $extension);
                }
            }
        }
    }

    public function update($table, $key, $data, $attachments = array())
    {
        $where = array();
        $where[$this->getPrimaryKey($table)] = $key;
        $data = $this->filterValues($table, $data);
        $this->db->update($table, $data, $where);
        $this->attach($table, $key, $attachments);
    }

    public function attach($table, $key, $attachments)
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        foreach ($this->getAttachments($table) as $name => $allowed) {
            if (!empty($attachments[$name])) {
                $attachment = $attachments[$name]['tmp_name'];
                $mimetype = $finfo->file($attachment);
                if (!empty($allowed[$mimetype])) {
                    $folder = $this->attachmentsDirectory . DIRECTORY_SEPARATOR . $table . DIRECTORY_SEPARATOR . $key;
                    if (!is_dir($folder)) {
                        mkdir($folder, 0755, true);
                    }
                    // remove uploads of different extension
                    foreach (glob($folder . DIRECTORY_SEPARATOR . $name . '.*') as $filename) {
                        unlink($filename);
                    }
                    move_uploaded_file($attachment, $folder . DIRECTORY_SEPARATOR . $name . $allowed[$mimetype]);
                }
                // else silently ignoring upload
            }
        }
    }

    private function fetchPage($table, $params = array(), $filters)
    {
        $params = call_user_func($this->selectFilter, $table, $params);
        // add search params
        if (!empty($filters["$table-search"])) {
            $search = array();
            foreach ($this->schema[$table] as $name => $column) {
                $search[] = array("$name LIKE ?" => '%' . $filters["$table-search"] . '%');
            }
            $params[] = $search;
        }
        $total = $this->db->count($table, $params);

        $c = (object) array(
            'limit' => $this->maxPerPage,
            'total' => $total,
            'page' => max((isset($filters["$table-page"]) ? (int) $filters["$table-page"] : 0), 1),
            'pages' => ceil($total / $this->maxPerPage),
        );

        # aggr block
        $aggr_columns = array();
        foreach ($this->schema[$table] as $name => $col) {
            # its not a key and its numeric than we can fetch aggregate infos
            if (empty($col['pk']) && empty($col['ref'])
                && ($col['type'] == 'integer' || $col['type'] == 'numeric') ) {
                    $aggr_columns[] = "MIN($name), MAX($name), AVG($name), SUM($name)";
                }
        }
        if ($aggr_columns) {
            $aggr_columns = implode(', ', $aggr_columns);
            $c->aggregates = array();
            foreach ($this->db->select("$aggr_columns FROM $table", $params)->fetch() as $aggr => $val) {
                preg_match("/(.+)\((.+)\)/", $aggr, $m);
                if ($m) {
                    if (empty($c->aggregates[$m[2]]))
                        $c->aggregates[$m[2]] = array();
                    $c->aggregates[$m[2]][ strtolower($m[1]) ] = sprintf('%3.2f', $val);
                }
            }
        }

        $limit = $c->limit;
        $offset = ($c->page - 1) * $c->limit;
        $extra = $this->orderBy($table, $filters);
        $c->results = $this->db->select($table, $params, "$extra LIMIT $limit OFFSET $offset")->fetchAll();
        foreach ($c->results as &$result) {
            $this->addToString($table, $result);
            $this->addId($table, $result);
        }
        return $c;
    }


    private function addId($table, &$result)
    {
        if (empty($result->id)) {
            $pk = $this->getPrimaryKey($table);
            $result->id = $result->$pk;
        }
    }


    private function addToString($table, &$result)
    {
        // add __tostring property to results
        $pk = $this->getPrimaryKey($table);
        $tostring = '';
        foreach ($this->schema[$table] as $name => $column) {
            if (in_array($column['type'], array('text', 'varchar', 'date', 'datetime', 'time'))) {
                $tostring = $name;
                break;
            }
        }
        $result->__tostring = '#' . $result->$pk;
        if (!empty($result->$tostring))
            $result->__tostring .= ' - ' . S::truncate(strip_tags($result->$tostring), 32, '...');
    }


    private function orderBy($table, $filters)
    {
        $dir = 'ASC';
        if (empty($filters["$table-sort"])) {
            $sort = $this->getPrimaryKey($table);
        } else {
            $sort = $filters["$table-sort"];
            if ($sort[0] == '-') {
                $dir = 'DESC';
                $sort = substr($sort, 1);
            }
            if (!isset($this->schema[$table][$sort])) {
                $sort = $this->getPrimaryKey($table);
            }
        }
        return "ORDER BY $sort $dir";
    }


    private function filterValues($table, $data)
    {
        $return = array();
        foreach ($data as $key => $value) {
            if (!empty($this->schema[$table][$key])
                && (!empty($value) || !empty($this->schema[$table][$key]['null']))) {
                    $return[$key] = $value;
            }
        }
        return $return;
    }
}

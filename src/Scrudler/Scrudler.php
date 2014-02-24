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
        $this->schema = $config['schema_filter']( self::metadata($this->db->pdo, $config['db']['tag']) );
        $this->selectFilter = $config['select_filter'];
        $this->maxPerPage = $config['max_per_page'];
        $this->attachments = $config['attachments'];
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
        return empty($this->attachments) ? array() : $this->attachments[$table];
    }

    public function get($table, $key = null, array $filters = array())
    {
        // this is what we will return to the page
        $data = (object) array(
            'table' => $table,
            'key' => $key,
            'row' => null,
            'has' => array(),
            'schema' => $this->schema,
            'attachments' => $this->attachments,
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
                    move_uploaded_file($attachment, $folder . DIRECTORY_SEPARATOR . $name . $allowed[$mimetype]);
                }
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


    /**
     * Fetch current database schema metadata using reflection.
     *
     * @param \PDO $db
     * @return array Assoc containing schema simple metadata.
     *     False value are omitted for brevity, use `!empty`
     *     eg.
     *         array(
     *             table_name => array(
     *                 column_name => array(
     *                     'type' => type_name
     *                     'null' => boolean,
     *                     'default' => string,
     *                     'unique' => boolean,
     *                     'pk' => boolean,
     *                     'auto' => boolean,
     *                     'ref' => array(
     *                         'table' => table_name
     *                         'column' => column_name
     *                     )
     *                 )
     *             )
     *         )
     */
    public static function metadata(\PDO $db, $tag = '')
    {
        if ($tag && extension_loaded('apc')) {
            $metadata = apc_fetch('scrudler.metadata');
            if ($metadata && $metadata['tag'] == $tag) {
                return $metadata['value'];
            }
        }
        $schema = array();
        switch ($db->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
        case 'sqlite':
            $tables = $db->query("SELECT name FROM sqlite_master WHERE type IN ('table', 'view') AND name != 'sqlite_sequence' ORDER BY name")->fetchAll(\PDO::FETCH_COLUMN);
            foreach ($tables as $table) {
                $schema[ $table ] = array();
                // add columns info
                foreach ($db->query("PRAGMA table_info($table)")->fetchAll() as $col) {
                    $schema[ $table ][ $col->name ] = array(
                        'type' => $col->type,
                        'null' => $col->notnull ? null : true,
                        'default' => $col->dflt_value,
                        'unique' => $col->pk ? true : null,
                        'pk' => $col->pk ? true : null,
                    );
                    $schema[ $table ][ $col->name ] = array_filter(self::normalizeColumnType($schema[$table][$col->name]), function ($value) { return !is_null($value); });
                    if ($col->pk) {
                        // add info about auto increment
                        if ($schema[$table][$col->name]['type'] === 'integer') {
                            $schema[$table][$col->name]['auto'] = true;
                        }
                    }
                }
                // add unique columns
                foreach ($db->query("PRAGMA index_list($table)")->fetchAll() as $index) {
                    if ($index->unique) {
                        $column = $db->query("PRAGMA index_info({$index->name})")->fetch()->name;
                        $schema[$table][$column]['unique'] = true;
                    }
                }
                // add foreign keys references
                foreach ($db->query("PRAGMA foreign_key_list($table)")->fetchAll() as $fk) {
                    $schema[$table][$fk->from]['ref'] = array('table' => $fk->table, 'column' => $fk->to);
                }
            }
            break;
        case 'mysql':
            // < 5.0.1 describe does not work for views
            // get storage engine, if MyISAM throw exception does not support foreign key constraints
            $tables = $db->query("SHOW tables")->fetchAll(\PDO::FETCH_COLUMN);
            foreach ($tables as $table) {
                $schema[ $table ] = array();
                // add columns info
                foreach ($db->query("SHOW COLUMNS FROM $table")->fetchAll() as $col) {
                    $schema[ $table ][ $col->Field ] = array(
                        'type' => $col->Type,
                        'null' => $col->Null == 'YES' ? true : null,
                        'default' => $col->Default,
                        'unique' => in_array($col->Key, array('PRI', 'UNI')) ? true : null,
                        'pk' => $col->Key == 'PRI' ? true : null,
                        'auto' => $col->Extra == 'auto_increment' ? true : null,
                    );
                    $schema[ $table ][ $col->Field ] = array_filter(self::normalizeColumnType($schema[$table][$col->Field]), function ($value) { return !is_null($value); });
                }
                // add foreign key references
                $database = $db->query("SELECT database()")->fetchColumn();
                foreach ($db->query("SELECT table_name, column_name, referenced_table_name, referenced_column_name FROM information_schema.key_column_usage WHERE referenced_table_name IS NOT NULL AND table_schema = '$database'") as $col) {
                    $schema[$col->table_name][$col->column_name]['ref'] = array('table' => $col->referenced_table_name, 'column' => $col->referenced_column_name);
                }
            }
            break;
        default:
            throw new Exception('Unsupported PDO driver');
        }

        if ($tag && extension_loaded('apc')) {
            apc_store('scrudler.metadata', array('tag' => $tag, 'value' => $schema));
        }
        return $schema;
    }

    public static function normalizeColumnType($col)
    {
        // char, varchar, text, boolean|bit, int, num, date, time, datetime, year, blob
        if (preg_match('/\((.+)\)/', $col['type'], $matches)) {
            $col['precision'] = $matches[1];
            $col['type'] = preg_replace('/\(.*/', '', $col['type']);
        }
        $col['type'] = strtolower($col['type']);
        switch ($col['type']) {
        case 'bool':
        case 'tinyint':
            $col['type'] = 'boolean';
            break;
        case 'int':
            $col['type'] = 'integer';
            break;
        case 'nvarchar':
            $col['type'] = 'varchar';
            break;
        case 'float':
        case 'double':
        case 'decimal':
            $col['type'] = 'numeric';
            break;
        case 'timestamp':
            $col['type'] = 'datetime';
            break;
        case 'enum':
            $col['precision'] = array_map(function($e) { return trim($e, "'"); }, explode(',', $col['precision']));
        }
        return $col;
    }
}

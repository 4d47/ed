<?php
namespace Scrudler;

use Stringy\StaticStringy as S;

class Scrudler
{
    private $db;
    private $schema;
    private $selectFilter;
    private $maxPerPage;


    public function __construct(\PDO2 $pdo2)
    {
        global $config;
        $this->db = $pdo2;
        $this->schema = $config['schema_filter']( DatabaseIntrospector::introspect($this->db->pdo, $config['db']['tag']) );
        $this->selectFilter = $config['select_filter'];
        $this->maxPerPage = $config['max_per_page'];
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


    public function get($table, $key = null, array $filters = array())
    {
        // this is what we will return to the page
        $data = (object) array(
            'table' => $table,
            'key' => $key,
            'row' => null,
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
            $from = $this->buildFrom($table);
            if (! $data->row = $this->db->select($from, $where)->fetch()) {
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


    public function fetchColumn($table, $key, $column)
    {
        if (empty($this->schema[$table][$column])) {
            return null;
        }
        // would probably better to return a stream but
        // https://bugs.php.net/bug.php?id=40913
        return $this->db->select("$column FROM $table")->fetchColumn();
    }


    public function create($table, array $data, array $files = array())
    {
        $data = $this->filterValues($table, $data, $files);
        $this->db->insert($table, $data);
        return $this->db->lastInsertId();
    }


    public function delete($table, $key)
    {
        $where = array();
        $where[$this->getPrimaryKey($table)] = $key;
        $this->db->delete($table, $where);
    }


    public function update($table, $key, array $data, array $files = array())
    {
        $where = array();
        $where[$this->getPrimaryKey($table)] = $key;
        $data = $this->filterValues($table, $data, $files);
        $this->db->update($table, $data, $where);
    }


    private function fetchPage($table, array $params = array(), $filters)
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
        $from = $this->buildFrom($table);
        $c->results = $this->db->select($from, $params, "$extra LIMIT $limit OFFSET $offset")->fetchAll();
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


    private function orderBy($table, array $filters)
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


    private function filterValues($table, array $data, array $files)
    {
        $return = array();
        foreach ($data as $col => $value) {
            if (!empty($this->schema[$table][$col])
                && (!empty($value) || !empty($this->schema[$table][$col]['null']))) {
                    $return[$col] = $value;
            }
        }
        foreach ($files as $col => $file) {
            if (!empty($this->schema[$table][$col]) && $file['error'] == UPLOAD_ERR_OK) {
                $return[$col] = file_get_contents($file['tmp_name']);
            }
        }
        return $return;
    }


    /**
     * Build custom from turning BLOB column into boolean
     */
    private function buildFrom($table)
    {
        $cols = array();
        foreach ($this->schema[$table] as $name => $column) {
            $cols[] = ($column['type'] == 'blob') ? "$name IS NOT NULL AS $name" : $name;
        }
        return implode(',', $cols) . " FROM $table";
    }
}

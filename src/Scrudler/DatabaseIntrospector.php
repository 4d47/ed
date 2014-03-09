<?php
namespace Scrudler;

class DatabaseIntrospector
{
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
    public static function introspect(\PDO $db, $tag = '')
    {
        if ($tag && extension_loaded('apc')) {
            $metadata = apc_fetch(__CLASS__ . '::metadata');
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
            apc_store(__CLASS__ . '::metadata', array('tag' => $tag, 'value' => $schema));
        }
        return $schema;
    }

    private static function normalizeColumnType($col)
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

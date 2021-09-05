<?php

namespace Lagdo\DbAdmin\DbAdmin;

use Exception;

/**
 * Admin export functions
 */
class ExportAdmin extends AbstractAdmin
{
    /**
     * The databases to dump
     *
     * @var array
     */
    protected $databases;

    /**
     * The tables to dump
     *
     * @var array
     */
    protected $tables;

    /**
     * The dump options
     *
     * @var array
     */
    protected $options;

    /**
     * The queries generated by the dump
     *
     * @var array
     */
    protected $queries = [];

    /**
     * Get data for export
     *
     * @param string $database      The database name
     * @param string $table
     *
     * @return array
     */
    public function getExportOptions(string $database, string $table = '')
    {
        // From dump.inc.php
        $db_style = ['', 'USE', 'DROP+CREATE', 'CREATE'];
        $table_style = ['', 'DROP+CREATE', 'CREATE'];
        $data_style = ['', 'TRUNCATE+INSERT', 'INSERT'];
        if ($this->db->jush() == 'sql') { //! use insertOrUpdate() in all drivers
            $data_style[] = 'INSERT+UPDATE';
        }
        // \parse_str($_COOKIE['adminer_export'], $row);
        // if(!$row) {
        $row = [
                'output' => 'text',
                'format' => 'sql',
                'db_style' => ($database != '' ? '' : 'CREATE'),
                'table_style' => 'DROP+CREATE',
                'data_style' => 'INSERT',
            ];
        // }
        // if(!isset($row['events'])) { // backwards compatibility
        $row['routines'] = $row['events'] = ($table == '');
        $row['triggers'] = $row['table_style'];
        // }
        $options = [
            'output' => [
                'label' => $this->util->lang('Output'),
                'options' => $this->util->dumpOutput(),
                'value' => $row['output'],
            ],
            'format' => [
                'label' => $this->util->lang('Format'),
                'options' => $this->util->dumpFormat(),
                'value' => $row['format'],
            ],
            'table_style' => [
                'label' => $this->util->lang('Tables'),
                'options' => $table_style,
                'value' => $row['table_style'],
            ],
            'auto_increment' => [
                'label' => $this->util->lang('Auto Increment'),
                'value' => 1,
                'checked' => $row['auto_increment'] ?? false,
            ],
            'data_style' => [
                'label' => $this->util->lang('Data'),
                'options' => $data_style,
                'value' => $row['data_style'],
            ],
        ];
        if ($this->db->jush() !== 'sqlite') {
            $options['db_style'] = [
                'label' => $this->util->lang('Database'),
                'options' => $db_style,
                'value' => $row['db_style'],
            ];
            if ($this->db->support('routine')) {
                $options['routines'] = [
                    'label' => $this->util->lang('Routines'),
                    'value' => 1,
                    'checked' => $row['routines'],
                ];
            }
            if ($this->db->support('event')) {
                $options['events'] = [
                    'label' => $this->util->lang('Events'),
                    'value' => 1,
                    'checked' => $row['events'],
                ];
            }
        }
        if ($this->db->support('trigger')) {
            $options['triggers'] = [
                'label' => $this->util->lang('Triggers'),
                'value' => 1,
                'checked' => $row['triggers'],
            ];
        }

        $results = [
            'options' => $options,
            'prefixes' => [],
        ];
        if (($database)) {
            $tables = [
                'headers' => [$this->util->lang('Tables'), $this->util->lang('Data')],
                'details' => [],
            ];
            $tables_list = $this->db->tables();
            foreach ($tables_list as $name => $type) {
                $prefix = \preg_replace('~_.*~', '', $name);
                //! % may be part of table name
                // $checked = ($TABLE == "" || $TABLE == (\substr($TABLE, -1) == "%" ? "$prefix%" : $name));
                // $results['prefixes'][$prefix]++;

                $tables['details'][] = \compact('prefix', 'name', 'type'/*, 'checked'*/);
            }
            $results['tables'] = $tables;
        } else {
            $databases = [
                'headers' => [$this->util->lang('Database'), $this->util->lang('Data')],
                'details' => [],
            ];
            $databases_list = $this->db->databases(false) ?? [];
            foreach ($databases_list as $name) {
                if (!$this->db->isInformationSchema($name)) {
                    $prefix = \preg_replace('~_.*~', '', $name);
                    // $results['prefixes'][$prefix]++;

                    $databases['details'][] = \compact('prefix', 'name');
                }
            }
            $results['databases'] = $databases;
        }

        $results['options'] = $options;
        $results['labels'] = [
            'export' => $this->util->lang('Export'),
        ];
        return $results;
    }

    /**
     * Dump routines and events in the connected database
     *
     * @param string $database      The database name
     *
     * @return void
     */
    protected function dumpRoutinesAndEvents(string $database)
    {
        // From dump.inc.php
        $style = $this->options["db_style"];
        $queries = [];

        if ($this->options["routines"]) {
            $sql = "SHOW FUNCTION STATUS WHERE Db = " . $this->db->quote($database);
            foreach ($this->db->rows($sql, null, "-- ") as $row) {
                $sql = "SHOW CREATE FUNCTION " . $this->db->escapeId($row["Name"]);
                $create = $this->db->removeDefiner($this->db->result($sql, 2));
                $queries[] = $this->db->setUtf8mb4($create);
                if ($style != 'DROP+CREATE') {
                    $queries[] = "DROP FUNCTION IF EXISTS " . $this->db->escapeId($row["Name"]) . ";;";
                }
                $queries[] = "$create;;\n";
            }
            $sql = "SHOW PROCEDURE STATUS WHERE Db = " . $this->db->quote($database);
            foreach ($this->db->rows($sql, null, "-- ") as $row) {
                $sql = "SHOW CREATE PROCEDURE " . $this->db->escapeId($row["Name"]);
                $create = $this->db->removeDefiner($this->db->result($sql, 2));
                $queries[] = $this->db->setUtf8mb4($create);
                if ($style != 'DROP+CREATE') {
                    $queries[] = "DROP PROCEDURE IF EXISTS " . $this->db->escapeId($row["Name"]) . ";;";
                }
                $queries[] = "$create;;\n";
            }
        }

        if ($this->options["events"]) {
            foreach ($this->db->rows("SHOW EVENTS", null, "-- ") as $row) {
                $sql = "SHOW CREATE EVENT " . $this->db->escapeId($row["Name"]);
                $create = $this->db->removeDefiner($this->db->result($sql, 3));
                $queries[] = $this->db->setUtf8mb4($create);
                if ($style != 'DROP+CREATE') {
                    $queries[] = "DROP EVENT IF EXISTS " . $this->db->escapeId($row["Name"]) . ";;";
                }
                $queries[] = "$create;;\n";
            }
        }

        if (\count($queries) > 0) {
            $this->queries[] = "DELIMITER ;;\n";
            foreach ($queries as $query) {
                $this->queries[] = $query;
            }
            $this->queries[] = "DELIMITER ;;\n";
        }
    }

    /**
     * Print CSV row
     *
     * @param array  $row
     *
     * @return void
     */
    protected function dumpCsv(array $row)
    {
        // From functions.inc.php
        foreach ($row as $key => $val) {
            if (\preg_match('~["\n,;\t]|^0|\.\d*0$~', $val) || $val === "") {
                $row[$key] = '"' . \str_replace('"', '""', $val) . '"';
            }
        }
        $separator = $this->options["format"] == "csv" ? "," :
            ($this->options["format"] == "tsv" ? "\t" : ";");
        $this->queries[] = \implode($separator, $row);
    }

    /**
     * Convert a value to string
     *
     * @param mixed  $val
     * @param array  $field
     *
     * @return string
     */
    protected function convertToString($val, array $field)
    {
        // From functions.inc.php
        if ($val === null) {
            return "NULL";
        }
        return $this->db->unconvertField($field, \preg_match(
            $this->db->numberRegex(),
            $field["type"]
        ) && !\preg_match('~\[~', $field["full_type"]) &&
            \is_numeric($val) ? $val : $this->db->quote(($val === false ? 0 : $val)));
    }

    /**
     * Export table structure
     *
     * @param string $table
     * @param string $style
     * @param int    $is_view       0 table, 1 view, 2 temporary view table
     *
     * @return null prints data
     */
    protected function dumpTable(string $table, string $style, int $is_view = 0)
    {
        // From adminer.inc.php
        if ($this->options['format'] != "sql") {
            $this->queries[] = "\xef\xbb\xbf"; // UTF-8 byte order mark
            if ($style) {
                $this->dumpCsv(\array_keys($this->db->fields($table)));
            }
            return;
        }

        if ($is_view == 2) {
            $fields = [];
            foreach ($this->db->fields($table) as $name => $field) {
                $fields[] = $this->db->escapeId($name) . ' ' . $field['full_type'];
            }
            $create = "CREATE TABLE " . $this->db->table($table) . " (" . \implode(", ", $fields) . ")";
        } else {
            $create = $this->db->createTableSql($table, $this->options['auto_increment'], $style);
        }
        $this->db->setUtf8mb4($create);
        if ($style && $create) {
            if ($style == "DROP+CREATE" || $is_view == 1) {
                $this->queries[] = "DROP " . ($is_view == 2 ? "VIEW" : "TABLE") .
                    " IF EXISTS " . $this->db->table($table) . ';';
            }
            if ($is_view == 1) {
                $create = $this->db->removeDefiner($create);
            }
            $this->queries[] = $create . ';';
        }
    }

    /** Export table data
     *
     * @param string
     * @param string
     * @param string
     *
     * @return null prints data
     */
    protected function dumpData($table, $style, $query)
    {
        $fields = [];
        $max_packet = ($this->db->jush() == "sqlite" ? 0 : 1048576); // default, minimum is 1024
        if ($style) {
            if ($this->options["format"] == "sql") {
                if ($style == "TRUNCATE+INSERT") {
                    $this->queries[] = $this->db->truncateTableSql($table) . ";\n";
                }
                $fields = $this->db->fields($table);
            }
            $statement = $this->db->query($query, 1); // 1 - MYSQLI_USE_RESULT //! enum and set as numbers
            if ($statement) {
                $insert = "";
                $buffer = "";
                $keys = [];
                $suffix = "";
                $fetch_function = ($table != '' ? 'fetchAssoc' : 'fetchRow');
                while ($row = $statement->$fetch_function()) {
                    if (!$keys) {
                        $values = [];
                        foreach ($row as $val) {
                            $field = $statement->fetchField();
                            $keys[] = $field->name;
                            $key = $this->db->escapeId($field->name);
                            $values[] = "$key = VALUES($key)";
                        }
                        $suffix = ";\n";
                        if ($style == "INSERT+UPDATE") {
                            $suffix = "\nON DUPLICATE KEY UPDATE " . \implode(", ", $values) . ";\n";
                        }
                    }
                    if ($this->options["format"] != "sql") {
                        if ($style == "table") {
                            $this->dumpCsv($keys);
                            $style = "INSERT";
                        }
                        $this->dumpCsv($row);
                    } else {
                        if (!$insert) {
                            $insert = "INSERT INTO " . $this->db->table($table) . " (" .
                                \implode(", ", \array_map(function ($key) {
                                    return $this->db->escapeId($key);
                                }, $keys)) . ") VALUES";
                        }
                        foreach ($row as $key => $val) {
                            $field = $fields[$key];
                            $row[$key] = $this->convertToString($val, $field);
                        }
                        $s = ($max_packet ? "\n" : " ") . "(" . \implode(",\t", $row) . ")";
                        if (!$buffer) {
                            $buffer = $insert . $s;
                        } elseif (\strlen($buffer) + 4 + \strlen($s) + \strlen($suffix) < $max_packet) { // 4 - length specification
                            $buffer .= ",$s";
                        } else {
                            $this->queries[] = $buffer . $suffix;
                            $buffer = $insert . $s;
                        }
                    }
                }
                if ($buffer) {
                    $this->queries[] = $buffer . $suffix;
                }
            } elseif ($this->options["format"] == "sql") {
                $this->queries[] = "-- " . \str_replace("\n", " ", $this->db->error()) . "\n";
            }
        }
    }

    /**
     * Dump tables and views in the connected database
     *
     * @param string $database      The database name
     *
     * @return array
     */
    protected function dumpTablesAndViews(string $database)
    {
        if (!$this->options["table_style"] && !$this->options["data_style"]) {
            return [];
        }

        $dbDumpTable = $this->tables['list'] === '*' &&
            \in_array($database, $this->databases["list"]);
        $dbDumpData = \in_array($database, $this->databases["data"]);
        $views = [];
        $dbTables = $this->db->tableStatus('', true);
        foreach ($dbTables as $table => $tableStatus) {
            $dumpTable = $dbDumpTable || \in_array($table, $this->tables['list']);
            $dumpData = $dbDumpData || \in_array($table, $this->tables["data"]);
            if ($dumpTable || $dumpData) {
                // if($ext == "tar")
                // {
                //     $tmp_file = new TmpFile;
                //     ob_start([$tmp_file, 'write'], 1e5);
                // }

                $this->dumpTable(
                    $table,
                    ($dumpTable ? $this->options["table_style"] : ""),
                    ($this->db->isView($tableStatus) ? 2 : 0)
                );
                if ($this->db->isView($tableStatus)) {
                    $views[] = $table;
                } elseif ($dumpData) {
                    $fields = $this->db->fields($table);
                    $query = "SELECT *" . $this->db->convertFields($fields, $fields) .
                        " FROM " . $this->db->table($table);
                    $this->dumpData($table, $this->options["data_style"], $query);
                }
                if ($this->options['is_sql'] && $this->options["triggers"] && $dumpTable &&
                    ($triggers = $this->db->createTriggerSql($table))) {
                    $this->queries[] = "DELIMITER ;";
                    $this->queries[] = $triggers;
                    $this->queries[] = "DELIMITER ;";
                }

                // if($ext == "tar")
                // {
                //     ob_end_flush();
                //     tar_file((DB != "" ? "" : "$db/") . "$table.csv", $tmp_file);
                // } else
                if ($this->options['is_sql']) {
                    $this->queries[] = '';
                }
            }
        }

        // add FKs after creating tables (except in MySQL which uses SET FOREIGN_KEY_CHECKS=0)
        if ($this->db->support('fkeys_sql')) {
            foreach ($dbTables as $table => $tableStatus) {
                $dumpTable = true; // (DB == "" || \in_array($table, $this->options["tables"]));
                if ($dumpTable && !$this->db->isView($tableStatus)) {
                    $this->queries[] = $this->db->foreignKeysSql($table);
                }
            }
        }

        foreach ($views as $view) {
            $this->dumpTable($view, $this->options["table_style"], 1);
        }

        // if($ext == "tar") {
        //     $this->queries[] = pack("x512");
        // }
    }

    /**
     * Export databases
     *
     * @param array  $databases     The databases to dump
     * @param array  $tables        The tables to dump
     * @param array  $options       The export options
     *
     * @return array
     */
    public function exportDatabases(array $databases, array $tables, array $options)
    {
        // From dump.inc.php
        // $tables = array_flip($options["tables"]) + array_flip($options["data"]);
        // $ext = dump_headers((count($tables) == 1 ? key($tables) : DB), (DB == "" || count($tables) > 1));
        $options['is_sql'] = \preg_match('~sql~', $options["format"]);
        $this->databases = $databases;
        $this->tables = $tables;
        $this->options = $options;

        $headers = null;
        if ($this->options['is_sql']) {
            $headers = [
                'version' => $this->db->version(),
                'driver' => $this->db->name(),
                'server' => \str_replace("\n", " ", $this->db->serverInfo()),
                'sql' => false,
                'data_style' => false,
            ];
            if ($this->db->jush() == "sql") {
                $headers['sql'] = true;
                if (isset($options["data_style"])) {
                    $headers['data_style'] = true;
                }
                // Set some options in database server
                $this->db->query("SET time_zone = '+00:00'");
                $this->db->query("SET sql_mode = ''");
            }
        }

        $style = $options["db_style"];

        foreach (\array_unique(\array_merge($databases['list'], $databases['data'])) as $db) {
            // $this->util->dumpDatabase($db);
            if ($this->db->selectDatabase($db)) {
                $sql = "SHOW CREATE DATABASE " . $this->db->escapeId($db);
                if ($this->options['is_sql'] && \preg_match('~CREATE~', $style) &&
                    ($create = $this->db->result($sql, 1))) {
                    $this->db->setUtf8mb4($create);
                    if ($style == "DROP+CREATE") {
                        $this->queries[] = "DROP DATABASE IF EXISTS " . $this->db->escapeId($db) . ";";
                    }
                    $this->queries[] = $create . ";\n";
                }
                if ($this->options['is_sql']) {
                    if ($style) {
                        if (($query = $this->db->useDatabaseSql($db))) {
                            $this->queries[] = $query . ";";
                        }
                        $this->queries[] = ''; // Empty line
                    }

                    $this->dumpRoutinesAndEvents($db);
                }

                $this->dumpTablesAndViews($db);
            }
        }

        if ($this->options['is_sql']) {
            $this->queries[] = "-- " . $this->db->result("SELECT NOW()");
        }

        return [
            'headers' => $headers,
            'queries' => $this->queries,
        ];
    }
}

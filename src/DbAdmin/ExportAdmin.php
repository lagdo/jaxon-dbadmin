<?php

namespace Lagdo\DbAdmin\DbAdmin;

use Exception;

use function count;
use function compact;
use function preg_replace;
use function preg_match;
use function str_replace;
use function array_unique;
use function array_merge;

/**
 * Admin export functions
 */
class ExportAdmin extends AbstractAdmin
{
    use Traits\DbDumpTrait;

    /**
     * The databases to dump
     *
     * @var array
     */
    private $databases;

    /**
     * The tables to dump
     *
     * @var array
     */
    private $tables;

    /**
     * @param string $database
     * @param string $table
     *
     * @return array
     */
    private function getBaseOptions(string $database, string $table): array
    {
        // From dump.inc.php
        $db_style = ['', 'USE', 'DROP+CREATE', 'CREATE'];
        $table_style = ['', 'DROP+CREATE', 'CREATE'];
        $data_style = ['', 'TRUNCATE+INSERT', 'INSERT'];
        if ($this->driver->jush() == 'sql') { //! use insertOrUpdate() in all drivers
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
                'label' => $this->trans->lang('Output'),
                'options' => $this->util->dumpOutput(),
                'value' => $row['output'],
            ],
            'format' => [
                'label' => $this->trans->lang('Format'),
                'options' => $this->util->dumpFormat(),
                'value' => $row['format'],
            ],
            'table_style' => [
                'label' => $this->trans->lang('Tables'),
                'options' => $table_style,
                'value' => $row['table_style'],
            ],
            'auto_increment' => [
                'label' => $this->trans->lang('Auto Increment'),
                'value' => 1,
                'checked' => $row['autoIncrement'] ?? false,
            ],
            'data_style' => [
                'label' => $this->trans->lang('Data'),
                'options' => $data_style,
                'value' => $row['data_style'],
            ],
        ];
        if ($this->driver->jush() !== 'sqlite') {
            $options['db_style'] = [
                'label' => $this->trans->lang('Database'),
                'options' => $db_style,
                'value' => $row['db_style'],
            ];
            if ($this->driver->support('routine')) {
                $options['routines'] = [
                    'label' => $this->trans->lang('Routines'),
                    'value' => 1,
                    'checked' => $row['routines'],
                ];
            }
            if ($this->driver->support('event')) {
                $options['events'] = [
                    'label' => $this->trans->lang('Events'),
                    'value' => 1,
                    'checked' => $row['events'],
                ];
            }
        }
        if ($this->driver->support('trigger')) {
            $options['triggers'] = [
                'label' => $this->trans->lang('Triggers'),
                'value' => 1,
                'checked' => $row['triggers'],
            ];
        }
        return $options;
    }

    /**
     * @return array
     */
    private function getDbTables(): array
    {
        $tables = [
            'headers' => [$this->trans->lang('Tables'), $this->trans->lang('Data')],
            'details' => [],
        ];
        $tables_list = $this->driver->tables();
        foreach ($tables_list as $name => $type) {
            $prefix = preg_replace('~_.*~', '', $name);
            //! % may be part of table name
            // $checked = ($TABLE == '' || $TABLE == (\substr($TABLE, -1) == '%' ? "$prefix%" : $name));
            // $results['prefixes'][$prefix]++;

            $tables['details'][] = compact('prefix', 'name', 'type'/*, 'checked'*/);
        }
        return $tables;
    }

    /**
     * @return array
     */
    private function getDatabases(): array
    {
        $databases = [
            'headers' => [$this->trans->lang('Database'), $this->trans->lang('Data')],
            'details' => [],
        ];
        $databases_list = $this->driver->databases(false) ?? [];
        foreach ($databases_list as $name) {
            if (!$this->driver->isInformationSchema($name)) {
                $prefix = preg_replace('~_.*~', '', $name);
                // $results['prefixes'][$prefix]++;

                $databases['details'][] = compact('prefix', 'name');
            }
        }
        return $databases;
    }

    /**
     * Get data for export
     *
     * @param string $database      The database name
     * @param string $table
     *
     * @return array
     */
    public function getExportOptions(string $database, string $table = ''): array
    {
        $results = [
            'options' => $this->getBaseOptions($database, $table),
            'prefixes' => [],
            'labels' => [
                'export' => $this->trans->lang('Export'),
            ],
        ];
        if (($database)) {
            $results['tables'] = $this->getDbTables();
        } else {
            $results['databases'] = $this->getDatabases();
        }
        return $results;
    }

    /**
     * Dump routines and events in the connected database
     *
     * @param string $database      The database name
     *
     * @return void
     */
    private function dumpRoutinesAndEvents(string $database)
    {
        // From dump.inc.php
        $style = $this->options['db_style'];
        $queries = [];

        if ($this->options['routines']) {
            $sql = 'SHOW FUNCTION STATUS WHERE Db = ' . $this->driver->quote($database);
            foreach ($this->driver->rows($sql) as $row) {
                $sql = 'SHOW CREATE FUNCTION ' . $this->driver->escapeId($row['Name']);
                $create = $this->admin->removeDefiner($this->driver->result($sql, 2));
                $queries[] = $this->driver->setUtf8mb4($create);
                if ($style != 'DROP+CREATE') {
                    $queries[] = 'DROP FUNCTION IF EXISTS ' . $this->driver->escapeId($row['Name']) . ';;';
                }
                $queries[] = "$create;;\n";
            }
            $sql = 'SHOW PROCEDURE STATUS WHERE Db = ' . $this->driver->quote($database);
            foreach ($this->driver->rows($sql) as $row) {
                $sql = 'SHOW CREATE PROCEDURE ' . $this->driver->escapeId($row['Name']);
                $create = $this->admin->removeDefiner($this->driver->result($sql, 2));
                $queries[] = $this->driver->setUtf8mb4($create);
                if ($style != 'DROP+CREATE') {
                    $queries[] = 'DROP PROCEDURE IF EXISTS ' . $this->driver->escapeId($row['Name']) . ';;';
                }
                $queries[] = "$create;;\n";
            }
        }

        if ($this->options['events']) {
            foreach ($this->driver->rows('SHOW EVENTS') as $row) {
                $sql = 'SHOW CREATE EVENT ' . $this->driver->escapeId($row['Name']);
                $create = $this->admin->removeDefiner($this->driver->result($sql, 3));
                $queries[] = $this->driver->setUtf8mb4($create);
                if ($style != 'DROP+CREATE') {
                    $queries[] = 'DROP EVENT IF EXISTS ' . $this->driver->escapeId($row['Name']) . ';;';
                }
                $queries[] = "$create;;\n";
            }
        }

        if (count($queries) > 0) {
            $this->queries[] = "DELIMITER ;;\n";
            foreach ($queries as $query) {
                $this->queries[] = $query;
            }
            $this->queries[] = "DELIMITER ;;\n";
        }
    }

    /**
     * @param string $database
     *
     * @return void
     */
    private function dumpDatabaseCreation(string $database)
    {
        $style = $this->options['db_style'];
        $this->driver->connect($database, '');
        $sql = 'SHOW CREATE DATABASE ' . $this->driver->escapeId($database);
        if ($this->options['is_sql'] && preg_match('~CREATE~', $style) &&
            ($create = $this->driver->result($sql, 1))) {
            $this->driver->setUtf8mb4($create);
            if ($style == 'DROP+CREATE') {
                $this->queries[] = 'DROP DATABASE IF EXISTS ' . $this->driver->escapeId($database) . ';';
            }
            $this->queries[] = $create . ";\n";
        }
    }

    /**
     * @param string $database
     *
     * @return void
     */
    private function dumpDatabase(string $database)
    {
        $this->dumpDatabaseCreation($database);
        if ($this->options['is_sql'] && $this->driver->jush() === 'sql') {
            // Dump routines and events currently works only for MySQL.
            if ($this->options['db_style']) {
                if (($query = $this->driver->sqlForUseDatabase($database))) {
                    $this->queries[] = $query . ';';
                }
                $this->queries[] = ''; // Empty line
            }
            $this->dumpRoutinesAndEvents($database);
        }

        if (!$this->options['table_style'] && !$this->options['data_style']) {
            return;
        }

        $this->dumpTables($database);
        $this->dumpViewsAndFKeys();
    }

    /**
     * Export databases
     *
     * @param array  $databases     The databases to dump
     * @param array  $tables        The tables to dump
     * @param array  $options       The export options
     *
     * @return array|string
     */
    public function exportDatabases(array $databases, array $tables, array $options)
    {
        // From dump.inc.php
        // $tables = array_flip($options['tables']) + array_flip($options['data']);
        // $ext = dump_headers((count($tables) == 1 ? key($tables) : DB), (DB == '' || count($tables) > 1));
        $options['is_sql'] = preg_match('~sql~', $options['format']);
        $this->databases = $databases;
        $this->tables = $tables;
        $this->options = $options;

        $headers = null;
        if ($this->options['is_sql']) {
            $headers = [
                'version' => $this->driver->version(),
                'driver' => $this->driver->name(),
                'server' => str_replace("\n", ' ', $this->driver->serverInfo()),
                'sql' => false,
                'data_style' => false,
            ];
            if ($this->driver->jush() == 'sql') {
                $headers['sql'] = true;
                if (isset($options['data_style'])) {
                    $headers['data_style'] = true;
                }
                // Set some options in database server
                $this->driver->query("SET time_zone = '+00:00'");
                $this->driver->query("SET sql_mode = ''");
            }
        }

        foreach (array_unique(array_merge($databases['list'], $databases['data'])) as $database) {
            try {
                $this->dumpDatabase($database);
            }
            catch (Exception $e) {
                return $e->getMessage();
            }
        }

        if ($this->options['is_sql']) {
            $this->queries[] = '-- ' . $this->driver->result('SELECT NOW()');
        }

        return [
            'headers' => $headers,
            'queries' => $this->queries,
        ];
    }
}

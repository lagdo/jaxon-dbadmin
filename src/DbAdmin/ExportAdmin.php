<?php

namespace Lagdo\DbAdmin\DbAdmin;

use Exception;

use function count;
use function in_array;
use function preg_match;
use function str_replace;
use function array_unique;
use function array_merge;
use function array_pop;

/**
 * Admin export functions
 */
class ExportAdmin extends AbstractAdmin
{
    use Traits\TableExportTrait;
    use Traits\TableDumpTrait;

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
     * Dump routines in the connected database
     *
     * @param string $database      The database name
     *
     * @return void
     */
    private function dumpRoutines(string $database)
    {
        // From dump.inc.php
        $style = $this->options['db_style'];

        if ($this->options['routines']) {
            $sql = 'SHOW FUNCTION STATUS WHERE Db = ' . $this->driver->quote($database);
            foreach ($this->driver->rows($sql) as $row) {
                $sql = 'SHOW CREATE FUNCTION ' . $this->driver->escapeId($row['Name']);
                $create = $this->admin->removeDefiner($this->driver->result($sql, 2));
                $this->queries[] = $this->driver->setUtf8mb4($create);
                if ($style != 'DROP+CREATE') {
                    $this->queries[] = 'DROP FUNCTION IF EXISTS ' . $this->driver->escapeId($row['Name']) . ';;';
                }
                $this->queries[] = "$create;;\n";
            }
            $sql = 'SHOW PROCEDURE STATUS WHERE Db = ' . $this->driver->quote($database);
            foreach ($this->driver->rows($sql) as $row) {
                $sql = 'SHOW CREATE PROCEDURE ' . $this->driver->escapeId($row['Name']);
                $create = $this->admin->removeDefiner($this->driver->result($sql, 2));
                $this->queries[] = $this->driver->setUtf8mb4($create);
                if ($style != 'DROP+CREATE') {
                    $this->queries[] = 'DROP PROCEDURE IF EXISTS ' . $this->driver->escapeId($row['Name']) . ';;';
                }
                $this->queries[] = "$create;;\n";
            }
        }
    }

    /**
     * Dump events in the connected database
     *
     * @return void
     */
    private function dumpEvents()
    {
        // From dump.inc.php
        $style = $this->options['db_style'];

        if ($this->options['events']) {
            foreach ($this->driver->rows('SHOW EVENTS') as $row) {
                $sql = 'SHOW CREATE EVENT ' . $this->driver->escapeId($row['Name']);
                $create = $this->admin->removeDefiner($this->driver->result($sql, 3));
                $this->queries[] = $this->driver->setUtf8mb4($create);
                if ($style != 'DROP+CREATE') {
                    $this->queries[] = 'DROP EVENT IF EXISTS ' . $this->driver->escapeId($row['Name']) . ';;';
                }
                $this->queries[] = "$create;;\n";
            }
        }
    }

    /**
     * @param string $table
     * @param bool $dumpTable
     * @param bool $dumpData
     *
     * @return void
     */
    private function dumpTable(string $table, bool $dumpTable, bool $dumpData)
    {
        $this->dumpTableOrView($table, ($dumpTable ? $this->options['table_style'] : ''));
        if ($dumpData) {
            $fields = $this->driver->fields($table);
            $query = 'SELECT *' . $this->driver->convertFields($fields, $fields) .
                ' FROM ' . $this->driver->table($table);
            $this->dumpData($table, $query);
        }
        if ($this->options['is_sql'] && $this->options['triggers'] && $dumpTable &&
            ($triggers = $this->driver->sqlForCreateTrigger($table))) {
            $this->queries[] = 'DELIMITER ;';
            $this->queries[] = $triggers;
            $this->queries[] = 'DELIMITER ;';
        }
        if ($this->options['is_sql']) {
            $this->queries[] = '';
        }
    }

    /**
     * @param string $database      The database name
     *
     * @return void
     */
    private function dumpTables(string $database)
    {
        $dbDumpTable = $this->tables['list'] === '*' && in_array($database, $this->databases['list']);
        $dbDumpData = in_array($database, $this->databases['data']);
        $this->views = []; // View names
        $this->fkeys = []; // Table names for foreign keys
        $dbTables = $this->driver->tableStatuses(true);
        foreach ($dbTables as $table => $tableStatus) {
            $isView = $this->driver->isView($tableStatus);
            if ($isView) {
                // The views will be dumped after the tables
                $this->views[] = $table;
                continue;
            }
            $this->fkeys[] = $table;
            $dumpTable = $dbDumpTable || in_array($table, $this->tables['list']);
            $dumpData = $dbDumpData || in_array($table, $this->tables['data']);
            if ($dumpTable || $dumpData) {
                $this->dumpTable($table, $dumpTable, $dumpData);
            }
        }
    }

    /**
     * @return void
     */
    private function dumpViewsAndFKeys()
    {
        // Add FKs after creating tables (except in MySQL which uses SET FOREIGN_KEY_CHECKS=0)
        if ($this->driver->support('fkeys_sql')) {
            foreach ($this->fkeys as $table) {
                $this->queries[] = $this->driver->sqlForForeignKeys($table);
            }
        }
        // Dump the views after all the tables
        foreach ($this->views as $view) {
            $this->dumpTableOrView($view, $this->options['table_style'], 1);
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
            $this->queries[] = $create . ';';
            $this->queries[] = ''; // Empty line
        }
        if ($this->options['is_sql'] && $this->options['db_style'] && $this->driver->jush() === 'sql') {
            if (($query = $this->driver->sqlForUseDatabase($database))) {
                $this->queries[] = $query . ';';
            }
            $this->queries[] = ''; // Empty line
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
            $count = count($this->queries);
            $this->queries[] = "DELIMITER ;;\n";
            // Dump routines and events currently works only for MySQL.
            $this->dumpRoutines($database);
            $this->dumpEvents();
            $this->queries[] = "DELIMITER ;;\n";
            if ($count + 2 === count($this->queries)) {
                // No routine or event were dumped, so the last 2 entries are removed.
                array_pop($this->queries);
                array_pop($this->queries);
            }
        }

        if (!$this->options['table_style'] && !$this->options['data_style']) {
            return;
        }

        $this->dumpTables($database);
        $this->dumpViewsAndFKeys();
    }

    /**
     * @return array|null
     */
    private function getDatabaseExportHeaders()
    {
        if (!$this->options['is_sql']) {
            return null;
        }
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
        return $headers;
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

        $headers = $this->getDatabaseExportHeaders();

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

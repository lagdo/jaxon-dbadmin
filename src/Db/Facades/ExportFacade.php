<?php

namespace Lagdo\DbAdmin\Db\Facades;

use Exception;

use function count;
use function preg_match;
use function str_replace;
use function array_unique;
use function array_merge;
use function array_pop;

/**
 * Facade to export functions
 */
class ExportFacade extends AbstractFacade
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
                'export' => $this->utils->trans->lang('Export'),
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
                $create = $this->driver->removeDefiner($this->driver->result($sql, 2));
                $this->queries[] = $this->driver->setUtf8mb4($create);
                if ($style != 'DROP+CREATE') {
                    $this->queries[] = 'DROP FUNCTION IF EXISTS ' . $this->driver->escapeId($row['Name']) . ';;';
                }
                $this->queries[] = "$create;;\n";
            }
            $sql = 'SHOW PROCEDURE STATUS WHERE Db = ' . $this->driver->quote($database);
            foreach ($this->driver->rows($sql) as $row) {
                $sql = 'SHOW CREATE PROCEDURE ' . $this->driver->escapeId($row['Name']);
                $create = $this->driver->removeDefiner($this->driver->result($sql, 2));
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
                $create = $this->driver->removeDefiner($this->driver->result($sql, 3));
                $this->queries[] = $this->driver->setUtf8mb4($create);
                if ($style != 'DROP+CREATE') {
                    $this->queries[] = 'DROP EVENT IF EXISTS ' . $this->driver->escapeId($row['Name']) . ';;';
                }
                $this->queries[] = "$create;;\n";
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
                $this->queries[] = $this->driver->getForeignKeysQuery($table);
            }
        }
        // Dump the views after all the tables
        foreach ($this->views as $view) {
            $this->dumpCreateTableOrView($view, $this->options['table_style'], 1);
        }
    }

    /**
     * @param string $database
     *
     * @return string
     */
    private function getCreateDatabaseQuery(string $database): string
    {
        if (!$this->options['is_sql'] || preg_match('~CREATE~', $this->options['db_style']) === false) {
            return '';
        }
        $sql = 'SHOW CREATE DATABASE ' . $this->driver->escapeId($database);
        return $this->driver->result($sql, 1);
    }

    /**
     * @param string $database
     *
     * @return void
     */
    private function dumpUseDatabaseQuery(string $database)
    {
        if (!$this->options['is_sql'] || !$this->options['db_style'] || $this->driver->jush() !== 'sql') {
            return;
        }
        if (($query = $this->driver->getUseDatabaseQuery($database))) {
            $this->queries[] = $query . ';';
            $this->queries[] = ''; // Empty line
        }
    }

    /**
     * @param string $database
     *
     * @return void
     */
    private function dumpCreateDatabaseQuery(string $database)
    {
        if (!($create = $this->getCreateDatabaseQuery($database))) {
            return;
        }
        if (($query = $this->driver->setUtf8mb4($create))) {
            $this->queries[] = $query . ';';
        }
        if ($this->options['db_style'] === 'DROP+CREATE') {
            $this->queries[] = 'DROP DATABASE IF EXISTS ' . $this->driver->escapeId($database) . ';';
        }
        $this->queries[] = $create . ';';
        $this->queries[] = ''; // Empty line
    }

    /**
     * @param string $database
     *
     * @return void
     */
    private function dumpDatabase(string $database)
    {
        $this->driver->open($database); // New connection
        $this->dumpCreateDatabaseQuery($database);
        $this->dumpUseDatabaseQuery($database);
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
    private function getDatabaseExportHeaders(): ?array
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
            if (isset($this->options['data_style'])) {
                $headers['data_style'] = true;
            }
            // Set some options in database server
            $this->driver->execute("SET time_zone = '+00:00'");
            $this->driver->execute("SET sql_mode = ''");
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

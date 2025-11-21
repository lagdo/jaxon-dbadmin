<?php

namespace Lagdo\DbAdmin\Db\Facades\Traits;

use function array_keys;
use function implode;

trait TableDumpTrait
{
    use TableDataDumpTrait;

    // Temp vars for table dumps
    private $views = [];
    private $fkeys = [];

    /**
     * @param string $table
     * @param string $style
     * @param int $tableType
     *
     * @return string
     */
    private function getCreateQuery(string $table, string $style, int $tableType): string
    {
        if ($tableType !== 2) {
            return $this->driver->getCreateTableQuery($table, $this->options['autoIncrement'], $style);
        }
        $fields = [];
        foreach ($this->driver->fields($table) as $name => $field) {
            $fields[] = $this->driver->escapeId($name) . ' ' . $field->fullType;
        }
        return 'CREATE TABLE ' . $this->driver->escapeTableName($table) . ' (' . implode(', ', $fields) . ')';
    }

    /**
     * Export table structure
     *
     * @param string $table
     * @param string $style
     * @param int    $tableType       0 table, 1 view, 2 temporary view table
     *
     * @return void
     */
    private function addCreateQuery(string $table, string $style, int $tableType)
    {
        $create = $this->getCreateQuery($table, $style, $tableType);
        $this->driver->setUtf8mb4($create);
        if (!$create) {
            return;
        }
        if ($style === 'DROP+CREATE' || $tableType === 1) {
            $this->queries[] = 'DROP ' . ($tableType === 2 ? 'VIEW' : 'TABLE') .
                ' IF EXISTS ' . $this->driver->escapeTableName($table) . ';';
        }
        if ($tableType === 1) {
            $create = $this->driver->removeDefiner($create);
        }
        $this->queries[] = $create . ';';
    }

    /**
     * Export table structure
     *
     * @param string $table
     * @param string $style
     * @param int    $tableType       0 table, 1 view, 2 temporary view table
     *
     * @return void
     */
    private function dumpCreateTableOrView(string $table, string $style, int $tableType = 0)
    {
        // From adminer.inc.php
        if ($this->options['format'] !== 'sql') {
            $this->queries[] = "\xef\xbb\xbf"; // UTF-8 byte order mark
            if ($style) {
                $this->dumpCsv(array_keys($this->driver->fields($table)));
            }
            return;
        }
        if (!$style) {
            return;
        }

        $this->addCreateQuery($table, $style, $tableType);
    }

    /**
     * @param string $table
     *
     * @return void
     */
    private function dumpTableTriggers(string $table)
    {
        if (($triggers = $this->driver->getCreateTriggerQuery($table))) {
            $this->queries[] = 'DELIMITER ;';
            $this->queries[] = $triggers;
            $this->queries[] = 'DELIMITER ;';
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
        $this->dumpCreateTableOrView($table, ($dumpTable ? $this->options['table_style'] : ''));
        if ($dumpData) {
            $this->dumpTableData($table);
        }
        if ($this->options['is_sql'] && $this->options['triggers'] && $dumpTable) {
            $this->dumpTableTriggers($table);
        }
        if ($this->options['is_sql']) {
            $this->queries[] = '';
        }
    }

    /**
     * @param array $tables
     *
     * @return void
     */
    private function dumpTables(array $tables)
    {
        $this->views = []; // View names
        $this->fkeys = []; // Table names for foreign keys

        foreach ($this->driver->tableStatuses(true) as $table => $tableStatus) {
            if(!isset($tables[$tableStatus->name]) && !isset($tables['*'])) {
                continue;
            }

            if ($this->driver->isView($tableStatus)) {
                // The views will be dumped after the tables
                $this->views[] = $table;
                continue;
            }

            $dump = $tables[$tableStatus->name] ?? $tables['*'];
            $this->dumpTable($table, $dump['table'], $dump['data']);
        }
    }
}

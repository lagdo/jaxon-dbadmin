<?php

namespace Lagdo\DbAdmin\DbAdmin\Traits;

use Lagdo\DbAdmin\Driver\Entity\TableEntity;

use function implode;
use function array_keys;
use function in_array;

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
    private function getCreateTableQuery(string $table, string $style, int $tableType): string
    {
        if ($tableType !== 2) {
            return $this->driver->sqlForCreateTable($table, $this->options['autoIncrement'], $style);
        }
        $fields = [];
        foreach ($this->driver->fields($table) as $name => $field) {
            $fields[] = $this->driver->escapeId($name) . ' ' . $field->fullType;
        }
        return 'CREATE TABLE ' . $this->driver->table($table) . ' (' . implode(', ', $fields) . ')';
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
    private function addCreateTableQuery(string $table, string $style, int $tableType)
    {
        $create = $this->getCreateTableQuery($table, $style, $tableType);
        $this->driver->setUtf8mb4($create);
        if (!$create) {
            return;
        }
        if ($style === 'DROP+CREATE' || $tableType === 1) {
            $this->queries[] = 'DROP ' . ($tableType === 2 ? 'VIEW' : 'TABLE') .
                ' IF EXISTS ' . $this->driver->table($table) . ';';
        }
        if ($tableType === 1) {
            $create = $this->admin->removeDefiner($create);
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

        $this->addCreateTableQuery($table, $style, $tableType);
    }

    /**
     * @param string $table
     *
     * @return void
     */
    private function dumpTableTriggers(string $table)
    {
        if (($triggers = $this->driver->sqlForCreateTrigger($table))) {
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
     * @param string $table
     * @param TableEntity $tableStatus
     * @param bool $dbDumpTable
     * @param bool $dbDumpData
     *
     * @return void
     */
    private function dumpTableOnly(string $table, TableEntity $tableStatus, bool $dbDumpTable, bool $dbDumpData)
    {
        if ($this->driver->isView($tableStatus)) {
            // The views will be dumped after the tables
            $this->views[] = $table;
            return;
        }
        $this->fkeys[] = $table;
        $dumpTable = $dbDumpTable || in_array($table, $this->tables['list']);
        $dumpData = $dbDumpData || in_array($table, $this->tables['data']);
        if ($dumpTable || $dumpData) {
            $this->dumpTable($table, $dumpTable, $dumpData);
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
            $this->dumpTableOnly($table, $tableStatus, $dbDumpTable, $dbDumpData);
        }
    }
}

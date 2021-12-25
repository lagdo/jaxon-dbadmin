<?php

namespace Lagdo\DbAdmin\DbAdmin\Traits;

use Lagdo\DbAdmin\Driver\Db\StatementInterface;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function count;
use function strlen;
use function preg_match;
use function str_replace;
use function implode;
use function is_numeric;
use function array_keys;
use function array_map;

trait TableDumpTrait
{
    /**
     * The dump options
     *
     * @var array
     */
    private $options;

    /**
     * The queries generated by the dump
     *
     * @var array
     */
    private $queries = [];

    // Temp vars for data dumps
    private $insert = '';
    private $buffer = '';
    private $suffix = '';
    private $views = [];
    private $fkeys = [];

    /**
     * Print CSV row
     *
     * @param array  $row
     *
     * @return void
     */
    private function dumpCsv(array $row)
    {
        // From functions.inc.php
        foreach ($row as $key => $val) {
            if (preg_match('~["\n,;\t]|^0|\.\d*0$~', $val) || $val === '') {
                $row[$key] = '"' . str_replace('"', '""', $val) . '"';
            }
        }
        $separator = $this->options['format'] === 'csv' ? ',' :
            ($this->options['format'] === 'tsv' ? "\t" : ';');
        $this->queries[] = implode($separator, $row);
    }

    /**
     * Convert a value to string
     *
     * @param mixed  $value
     * @param TableFieldEntity $field
     *
     * @return string
     */
    private function convertToString($value, TableFieldEntity $field): string
    {
        // From functions.inc.php
        if ($value === null) {
            return 'NULL';
        }
        if (!preg_match($this->driver->numberRegex(), $field->type) ||
            preg_match('~\[~', $field->fullType) && is_numeric($value)) {
            $value = $this->driver->quote(($value === false ? 0 : $value));
        }
        return $this->driver->unconvertField($field, $value);
    }

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
    private function dumpTableOrView(string $table, string $style, int $tableType = 0)
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
     * @param array $row
     * @param StatementInterface $statement
     *
     * @return array
     */
    private function getDataRowKeys(array $row, StatementInterface $statement): array
    {
        $values = [];
        $keys = [];
        // For is preferred to foreach because the values are not used.
        // foreach ($row as $val) {
        // }
        for ($i = 0; $i < count($row); $i++) {
            $field = $statement->fetchField();
            $keys[] = $field->name();
            $key = $this->driver->escapeId($field->name());
            $values[] = "$key = VALUES($key)";
        }
        $this->suffix = ";\n";
        if ($this->options['data_style'] === 'INSERT+UPDATE') {
            $this->suffix = "\nON DUPLICATE KEY UPDATE " . implode(', ', $values) . ";\n";
        }
        return $keys;
    }

    /**
     * @param array $row
     *
     * @return void
     */
    private function saveRowInBuffer(array $row)
    {
        $max_packet = ($this->driver->jush() === 'sqlite' ? 0 : 1048576); // default, minimum is 1024
        $s = ($max_packet ? "\n" : ' ') . '(' . implode(",\t", $row) . ')';
        if (!$this->buffer) {
            $this->buffer = $this->insert . $s;
            return;
        }
        if (strlen($this->buffer) + 4 + strlen($s) + strlen($this->suffix) < $max_packet) { // 4 - length specification
            $this->buffer .= ",$s";
            return;
        }
        $this->queries[] = $this->buffer . $this->suffix;
        $this->buffer = $this->insert . $s;
    }

    /**
     * @param string $table
     * @param array $fields
     * @param array $row
     * @param array $keys
     *
     * @return void
     */
    private function dumpRow(string $table, array $fields, array $row, array $keys)
    {
        if ($this->options['format'] !== 'sql') {
            if ($this->options['data_style'] === 'table') {
                $this->dumpCsv($keys);
                $this->options['data_style'] = 'INSERT';
            }
            $this->dumpCsv($row);
            return;
        }
        if (!$this->insert) {
            $this->insert = 'INSERT INTO ' . $this->driver->table($table) . ' (' .
                implode(', ', array_map(function ($key) {
                    return $this->driver->escapeId($key);
                }, $keys)) . ') VALUES';
        }
        foreach ($row as $key => $val) {
            $field = $fields[$key];
            $row[$key] = $this->convertToString($val, $field);
        }
        $this->saveRowInBuffer($row);
    }

    /**
     * @param string $table
     *
     * @return void
     */
    private function dumpTruncateTableQuery(string $table)
    {
        if ($this->options['format'] === 'sql' &&
            $this->options['data_style'] === 'TRUNCATE+INSERT') {
            $this->queries[] = $this->driver->sqlForTruncateTable($table) . ";\n";
        }
    }

    /**
     * @param string $table
     * @param StatementInterface $statement
     *
     * @return void
     */
    private function dumpRows(string $table, StatementInterface $statement)
    {
        $fields = $this->options['format'] !== 'sql' ? [] : $this->driver->fields($table);
        $keys = [];
        $fetch_function = ($table !== '' ? 'fetchAssoc' : 'fetchRow');
        while ($row = $statement->$fetch_function()) {
            if (empty($keys)) {
                $keys = $this->getDataRowKeys($row, $statement);
            }
            $this->dumpRow($table, $fields, $row, $keys);
        }
    }

    /** Export table data
     *
     * @param string $table
     * @param string $query
     *
     * @return void
     */
    private function dumpData(string $table, string $query)
    {
        if (!$this->options['data_style']) {
            return;
        }
        $statement = $this->driver->query($query); // 1 - MYSQLI_USE_RESULT //! enum and set as numbers
        if (!$statement) {
            if ($this->options['format'] === 'sql') {
                $this->queries[] = '-- ' . str_replace("\n", ' ', $this->driver->error()) . "\n";
            }
            return;
        }

        $this->insert = '';
        $this->buffer = '';
        $this->suffix = '';
        $this->dumpTruncateTableQuery($table);
        $this->dumpRows($table, $statement);
        if (!empty($this->buffer)) {
            $this->queries[] = $this->buffer . $this->suffix;
        }
    }
}

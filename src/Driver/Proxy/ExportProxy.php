<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\DataDump;
use Lagdo\DbAdmin\Support\Driver\UiDto\TableExport;
use Lagdo\DbAdmin\Driver\Sql\Connection\QueryResultInterface;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnType;
use Lagdo\DbAdmin\Driver\Sql\Dto\RoutineDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\RoutineInfoDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableDto;
use Exception;
use Generator;

use function array_filter;
use function array_keys;
use function array_map;
use function count;
use function implode;
use function is_numeric;
use function ksort;
use function preg_match;
use function rtrim;
use function str_replace;
use function trim;

/**
 * Proxy to export functions
 */
class ExportProxy extends AbstractDriverProxy
{
    /**
     * The dump options
     *
     * @var array
     */
    private array $options;

    /**
     * @var string
     */
    private string $timestamp;

    /**
     * @return TableExport
     */
    private function export(): TableExport
    {
        return new TableExport($this);
    }

    /**
     * Print CSV row
     *
     * @param array  $row
     *
     * @return Generator
     */
    private function dumpCsv(array $row): Generator
    {
        // From functions.inc.php
        foreach ($row as $key => $val) {
            if (preg_match('~["\n,;\t]|^0|\.\d*0$~', $val ?? '') || $val === '') {
                $row[$key] = '"' . str_replace('"', '""', $val) . '"';
            }
        }
        $separator = match($this->options['format']) {
            'csv' => ',',
            'tsv' => "\t",
            default => ';',
        };
        yield implode($separator, $row);
    }

    /**
     * Convert a value to string
     *
     * @param mixed $value
     * @param ColumnDto $column
     *
     * @return string
     */
    private function convertToString(mixed $value, ColumnDto $column): string
    {
        // From functions.inc.php
        if ($value === null) {
            return 'NULL';
        }

        if (!preg_match($this->engine()->numberRegex(), $column->type) ||
            preg_match('~\[~', $column->fullType) && is_numeric($value)) {
            $value = $this->engine()->quote(($value === false ? 0 : $value));
        }
        return $this->statement()->unconvertColumn($column, $value);
    }

    /**
     * @param string $table
     *
     * @return Generator
     */
    private function dumpTruncateQuery(string $table): Generator
    {
        if ($this->options['format'] === 'sql' &&
            $this->options['data_style'] === 'TRUNCATE+INSERT') {
            yield $this->statement()->getTruncateTableQuery($table) . ";\n";
        }
    }

    /**
     * @param DataDump $dump
     * @param array $row
     * @param QueryResultInterface $result
     *
     * @return array
     */
    private function getColumnNames(DataDump $dump, array $row, QueryResultInterface $result): array
    {
        // For each entry in the row, fetch a column and get the name.
        // Important: the values in the row are actually not used.
        $names = array_map(fn($_) => $result->fetchColumn()->name(), $row);
        $values = array_map(function(string $name) {
            $name = $this->statement()->escapeId($name);
            return "$name = VALUES($name)";
        }, $names);
        $dump->suffix = $this->options['data_style'] !== 'INSERT+UPDATE' ? ';' :
            "\nON DUPLICATE KEY UPDATE " . implode(', ', $values) . ';';

        return $names;
    }

    /**
     * @param DataDump $dump
     * @param array $columns
     * @param array $row
     * @param array $columnNames
     *
     * @return Generator
     */
    private function dumpRow(DataDump $dump, array $columns, array $row, array $columnNames): Generator
    {
        if ($this->options['format'] !== 'sql') {
            if ($this->options['data_style'] === 'table') {
                yield from $this->dumpCsv($columnNames);
                $this->options['data_style'] = 'INSERT';
            }
            yield from $this->dumpCsv($row);
            return;
        }

        if ($dump->insert === '') {
            $tableName = $this->statement()->escapeTableName($dump->table);
            $columnNames = implode(', ', array_map($this->statement()->escapeId(...), $columnNames));
            $dump->insert = "INSERT INTO $tableName ($columnNames) VALUES";
        }
        foreach ($row as $columnName => $value) {
            $column = $columns[$columnName];
            $row[$columnName] = $this->convertToString($value, $column);
        }

        $dump->addRow($row);
        if ($dump->limitExceeded()) {
            yield $dump->makeQuery();
        }
    }

    /**
     * @param DataDump $dump
     * @param QueryResultInterface $result
     *
     * @return Generator
     */
    private function dumpRows(DataDump $dump, QueryResultInterface $result): Generator
    {
        $columns = $this->options['format'] !== 'sql' ? [] : $this->engine()->columns($dump->table);
        $columnNames = null;
        $fetchStatement = $dump->table !== '' ?
            fn(QueryResultInterface $result) => $result->fetchAssoc() :
            fn(QueryResultInterface $result) => $result->fetchRow();
        while ($row = $fetchStatement($result)) {
            $columnNames ??= $this->getColumnNames($dump, $row, $result);
            yield from $this->dumpRow($dump, $columns, $row, $columnNames);
        }
        if (count($dump->dataRows) > 0) {
            yield $dump->makeQuery();
        }
    }

    /** Export table data
     *
     * @param string $table
     *
     * @return Generator
     */
    private function dumpTableData(string $table): Generator
    {
        if (!$this->options['data_style']) {
            return;
        }

        $columns = $this->engine()->columns($table);
        $columns = $this->statement()->convertColumns(array_keys($columns), $columns);
        $query = "SELECT *$columns FROM " . $this->statement()->escapeTableName($table);
        // 1 - MYSQLI_USE_RESULT //! enum and set as numbers
        $result = $this->engine()->executeQuery($query);
        if ($result->hasError()) {
            if ($this->options['format'] === 'sql') {
                yield '-- ' . str_replace("\n", ' ', $this->engine()->error()) . "\n";
            }
            return;
        }

        $maxRowSize = ($this->engine()->sqlite() ? 0 : 1048576); // default, minimum is 1024
        $separator = $maxRowSize > 0 ? "\n" : ' ';
        $dump = new DataDump($table, $maxRowSize, $separator);

        yield from $this->dumpTruncateQuery($table);
        yield from $this->dumpRows($dump, $result);
    }

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
            $autoIncrement = $this->options['autoIncrement'];
            return $this->statement()->getExportTableQueries($table, $autoIncrement, $style);
        }

        $columns = $this->engine()->columns($table);
        $callback = fn(ColumnDto $column, string $columnName) =>
            $this->statement()->escapeId($columnName) . ' ' . $column->fullType;
        $columns = implode(', ', array_map($callback, $columns, array_keys($columns)));

        $tableName = $this->statement()->escapeTableName($table);
        return "CREATE TABLE $tableName ($columns)";
    }

    /**
     * Export table structure
     *
     * @param string $table
     * @param string $style
     * @param int    $tableType       0 table, 1 view, 2 temporary view table
     *
     * @return Generator
     */
    private function addCreateQuery(string $table, string $style, int $tableType): Generator
    {
        $query = $this->getCreateQuery($table, $style, $tableType);
        if (!$query) {
            return;
        }

        $this->statement()->setUtf8mb4($query);
        if ($style === 'DROP+CREATE' || $tableType === 1) {
            yield 'DROP ' . ($tableType === 2 ? 'VIEW' : 'TABLE') .
                ' IF EXISTS ' . $this->statement()->escapeTableName($table) . ';';
        }
        if ($tableType === 1) {
            $query = $this->statement()->removeDefiner($query);
        }
        yield "$query;\n";
    }

    /**
     * Export table structure
     *
     * @param string $table
     * @param string $style
     * @param int    $tableType       0 table, 1 view, 2 temporary view table
     *
     * @return Generator
     */
    private function dumpCreateTableOrView(string $table, string $style, int $tableType = 0): Generator
    {
        // From adminer.inc.php
        if ($this->options['format'] !== 'sql') {
            yield "\xef\xbb\xbf"; // UTF-8 byte order mark
            if ($style) {
                yield from $this->dumpCsv(array_keys($this->engine()->columns($table)));
            }
            return;
        }
        if (!$style) {
            return;
        }

        yield from $this->addCreateQuery($table, $style, $tableType);
    }

    /**
     * @param string $table
     *
     * @return Generator
     */
    private function dumpTableTriggers(string $table): Generator
    {
        if (($triggers = $this->statement()->getCreateTriggerQuery($table)) !== '') {
            yield '';
            yield 'DELIMITER ;;';
            yield $triggers;
            yield 'DELIMITER ;';
        }
    }

    /**
     * @param TableDto $tableStatus
     * @param bool $dumpTable
     * @param bool $dumpData
     *
     * @return Generator
     */
    private function dumpTable(TableDto $tableStatus, bool $dumpTable, bool $dumpData): Generator
    {
        $style = $dumpTable ? $this->options['table_style'] : '';
        $tableType = $this->engine()->isView($tableStatus) ? 2 : 1;
        yield from $this->dumpCreateTableOrView($tableStatus->name, $style, $tableType);
        if ($dumpData) {
            yield from $this->dumpTableData($tableStatus->name);
        }
        if ($this->options['to_sql'] && $this->options['triggers'] && $dumpTable) {
            yield from $this->dumpTableTriggers($tableStatus->name);
        }
    }

    /**
     * @param array<TableDto> $tableStatuses
     * @param array $tableOptions
     *
     * @return Generator
     */
    private function dumpTables(array $tableStatuses, array $tableOptions): Generator
    {
        foreach ($tableStatuses as $status) {
            yield '';
            $options = $tableOptions[$status->name] ?? $tableOptions['*'];
            yield from $this->dumpTable($status, $options['table'], $options['data']);
        }
        if (!$this->engine()->pgsql()) {
            return;
        }

        // Add FKs after creating tables (except in MySQL which uses SET FOREIGN_KEY_CHECKS=0)
        yield '';
        $tables = array_filter($tableStatuses, $this->engine()->isTable(...));
        foreach ($tables as $status) {
            $queries = $this->statement()->getForeignKeyQueries($status);
            if (count($queries) > 0) {
                $queries[] = '';
            }
            foreach ($queries as $query) {
                yield $query;
            }
        }
    }

    /**
     * @param array $tableStatuses
     *
     * @return Generator
     */
    private function dumpViews(array $tableStatuses): Generator
    {
        $views = array_filter($tableStatuses, $this->engine()->isView(...));
        foreach ($views as $view) {
            yield from $this->dumpCreateTableOrView($view->name, $this->options['table_style'], 1);
        }
    }

    /**
     * Get data for export
     *
     * @param string $table
     *
     * @return array
     */
    public function getExportOptions(string $table = ''): array
    {
        $export = $this->export();
        $database = $this->currentDb()->name;
        return $database === '' ? [
            'databases' => $export->getDatabases(),
            'options' => $export->getBaseOptions($database, $table),
            'prefixes' => [],
        ] : [
            'tables' => $export->getDbTables(),
            'options' => $export->getBaseOptions($database, $table),
            'prefixes' => [],
        ];
    }

    /**
     * @return array
     */
    public function getExportSelection(): array
    {
        $export = $this->export();
        return [
            'output' => array_keys($export->getSelectOutputValues()),
            'format' => array_keys($export->getSelectFormatValues()),
            'db_style' => $export->getSelectDatabaseValues(),
            'table_style' => $export->getSelectTableValues(),
            'data_style' => $export->getSelectDataValues(),
        ];
    }

    /**
     * @param array<ColumnType> $params
     *
     * @return string
     */
    private function getRoutineParams(array $params): string
    {
        // From dump.inc.php create_routine()
        $params = array_filter($params, fn($param) => $param->name !== '');
        ksort($params); // enforce params order
        $regex = "~^(" . $this->engine()->inout() . ")\$~";

        $params = array_map(function($param) use($regex) {
            $inout = preg_match($regex, $param->inout) ? "{$param->inout} " : '';
            return $inout . $this->statement()->escapeId($param->name) .
                $this->statement()->getColumnType($param, 'CHARACTER SET');
        },$params);
        return implode(', ', $params);
    }

    /**
     * Generate SQL query for creating routine
     *
     * @param RoutineDto $routine
     * @param RoutineInfoDto $routineInfo
     *
     * @return string
     */
    private function getRoutineQuery(RoutineDto $routine, RoutineInfoDto $routineInfo): string
    {
        // From dump.inc.php create_routine()
        $routineName = $this->statement()->escapeId(trim($routine->name));
        $routineParams = $this->getRoutineParams($routineInfo->params);
        $routineReturns = $routine->type !== 'FUNCTION' ? '' :
            ' RETURNS' . $this->statement()->getColumnType($routineInfo->return, 'CHARACTER SET');
        $routineLanguage = $routineInfo->language ? " LANGUAGE {$routineInfo->language}" : '';
        $definition = rtrim($routineInfo->definition, ';');
        $routineDefinition = !$this->engine()->pgsql() ? "\n$definition;" :
            ' AS ' . $this->engine()->quote($definition);

        return "CREATE {$routine->type} $routineName ($routineParams)" .
            "{$routineReturns}{$routineLanguage}{$routineDefinition};";
    }

    /**
     * Dump types in the connected database
     *
     * @return Generator
     */
    private function dumpTypes(): Generator
    {
        if (!$this->options['types']) {
            return;
        }

        // From dump.inc.php
        $style = $this->options['db_style'];
        foreach ($this->engine()->userTypes(true) as $type) {
            yield ''; // Empty line
            if (count($type->enums) === 0) {
                //! https://github.com/postgres/postgres/blob/REL_17_4/src/bin/pg_dump/pg_dump.c#L10846
                yield "-- Could not export type {$type->name}";
                continue;
            }

            $typeName = $this->statement()->escapeId($type->name);
            if ($style !== 'DROP+CREATE') {
                yield "DROP TYPE IF EXISTS $typeName;;";
            }
            $enums = implode("', '", $type->enums);
            yield "CREATE TYPE $typeName AS ENUM ('$enums');";
        }
    }

    /**
     * Dump routines in the connected database
     *
     * @return Generator
     */
    private function dumpRoutines(): Generator
    {
        if (!$this->options['routines']) {
            return;
        }

        // From dump.inc.php
        $style = $this->options['db_style'];
        foreach ($this->engine()->routines() as $routine) {
            $routineName = $this->statement()->escapeId(trim($routine->name));
            $routineInfo = $this->engine()->routine($routine->specificName, $routine->type);
            if ($routineInfo === null) {
                continue;
            }

            $create = $this->getRoutineQuery($routine, $routineInfo);
            $this->statement()->setUtf8mb4($create);
            yield ''; // Empty line
            if ($style !== 'DROP+CREATE') {
                yield "DROP {$routine->type} IF EXISTS $routineName;;";
            }
            yield $create;
        }
    }

    /**
     * Dump events in the connected database
     *
     * @return Generator
     */
    private function dumpEvents(): Generator
    {
        if (!$this->options['events']) {
            return;
        }

        // From dump.inc.php
        $style = $this->options['db_style'];
        foreach ($this->engine()->rows('SHOW EVENTS') as $row) {
            $sql = 'SHOW CREATE EVENT ' . $this->statement()->escapeId($row['Name']);
            $create = $this->statement()->removeDefiner($this->engine()->columnValue($sql, 3));
            $this->statement()->setUtf8mb4($create);
            yield ''; // Empty line
            if ($style !== 'DROP+CREATE') {
                yield 'DROP EVENT IF EXISTS ' . $this->statement()->escapeId($row['Name']) . ';;';
            }
            yield "$create;;\n";
        }
    }

    /**
     * @param string $database
     *
     * @return Generator
     */
    private function dumpUseDatabaseQuery(string $database): Generator
    {
        $style = $this->options['db_style'] ?? '';
        if ($style !== '' && preg_match('~sql~', $this->options['format'])) {
            yield $this->statement()->getUseDatabaseQuery($database, $style);
        }
    }

    /**
     * @param string $database
     * @param array $tableOptions
     *
     * @return Generator
     */
    private function dumpDatabase(string $database, array $tableOptions): Generator
    {
        // Dump the database using the created connection.
        yield from $this->dumpUseDatabaseQuery($database);

        if ($this->options['to_sql']) {
            yield from $this->dumpTypes();
            yield from $this->dumpRoutines();
            yield from $this->dumpEvents();
        }

        if (!$this->options['table_style'] && !$this->options['data_style']) {
            return;
        }

        $statuses = array_filter($this->engine()->tableStatuses(true), fn($status) =>
            isset($tableOptions['*']) || isset($tableOptions[$status->name]));
        yield from $this->dumpTables($statuses, $tableOptions);
        // Dump the views after all the tables
        yield from $this->dumpViews($statuses);
    }

    /**
     * @return array
     */
    private function getDatabaseExportHeaders(): array
    {
        $headers = [
            'version' => $this->engine()->version(),
            'driver' => $this->engine()->name(),
            'server' => str_replace("\n", ' ', $this->engine()->serverInfo()),
            'sql' => false,
            'data_style' => false,
        ];
        if ($this->engine()->sql()) {
            $headers['sql'] = true;
            if (isset($this->options['data_style'])) {
                $headers['data_style'] = true;
            }
            // Set some options in database server
            $this->engine()->execute("SET time_zone = '+00:00'");
            $this->engine()->execute("SET sql_mode = ''");
        }
        return $headers;
    }

    /**
     * Export databases
     *
     * @param string $database      The database to dump
     * @param array  $tables        The tables to dump
     *
     * @return Generator
     */
    private function exportDatabase(string $database, array $tables): Generator
    {
        // New connection
        $this->engine()->openMainConnection($database);
        yield from $this->dumpDatabase($database, $tables);

        if ($this->options['to_sql']) {
            $this->timestamp = '-- ' . $this->engine()->columnValue('SELECT NOW()');
        }
        $this->engine()->closeConnection();
    }

    /**
     * Export databases
     *
     * @param array  $databases     The databases to dump
     * @param array  $options       The export options
     *
     * @return Generator|string
     */
    public function exportDatabases(array $databases, array $options): Generator|string
    {
        $this->options = $options;
        // Export to SQL format (renamed from is_sql to to_sql).
        $this->options['to_sql'] = preg_match('~sql~', $options['format']) === 1;

        if ($this->options['to_sql']) {
            [
                'version' => $version,
                'driver' => $driver,
                'server' => $server,
                'sql' => $isSql,
                'data_style' => $dataStyle,
            ] = $this->getDatabaseExportHeaders();

            yield "-- Jaxon DbAdmin {$version} {$driver} {$server} dump\n";
            if ($isSql) {
                yield "SET NAMES utf8;\nSET time_zone = '+00:00';";
                yield "SET foreign_key_checks = 0;";
                if ($dataStyle) {
                    yield "SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';";
                }
            }
        }

        foreach ($databases as $database => $tables) {
            try {
                yield from $this->exportDatabase($database, $tables);
            }
            catch (Exception $e) {
                return $e->getMessage();
            }
        }

        if ($this->options['to_sql']) {
            yield $this->timestamp;
        }
    }
}

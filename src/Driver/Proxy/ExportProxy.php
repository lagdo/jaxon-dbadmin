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
use function str_repeat;
use function str_replace;
use function trim;

/**
 * Proxy to export functions
 */
class ExportProxy extends AbstractDriverProxy
{
    /**
     * @var string
     */
    private $version = '0.37.2';

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
     * @var bool|null
     */
    private bool|null $toSql = null;

    /**
     * @return TableExport
     */
    private function export(): TableExport
    {
        return new TableExport($this);
    }

    /**
     * @return bool
     */
    private function toSql(): bool
    {
        // Export to SQL format.
        return $this->toSql ??= preg_match('~sql~', $this->options['format']) === 1;
    }

    /**
     * Append the new line char to a SQL query for output
     *
     * @param string $query
     * @param int $count
     *
     * @return string
     */
    private function nl(string $query, int $count = 1): string
    {
        return $query . str_repeat("\n", $count);
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
        yield $this->nl(implode($separator, $row));
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
            yield $this->nl($this->statement()->getTruncateTableQuery($table) . ";", 2);
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
            yield $this->nl($dump->makeQuery());
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
        $columns = $this->options['format'] !== 'sql' ?
            [] : $this->engine()->columns($dump->table);
        $columnNames = null;
        $fetchStatement = $dump->table !== '' ?
            fn(QueryResultInterface $result) => $result->fetchAssoc() :
            fn(QueryResultInterface $result) => $result->fetchRow();
        while ($row = $fetchStatement($result)) {
            $columnNames ??= $this->getColumnNames($dump, $row, $result);
            yield from $this->dumpRow($dump, $columns, $row, $columnNames);
        }
        if (count($dump->dataRows) > 0) {
            yield $this->nl($dump->makeQuery());
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
                $comment = str_replace("\n", ' ', $this->engine()->error());
                yield $this->nl("-- $comment" , 2);
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
            $tableClause = $tableType === 2 ? 'VIEW' : 'TABLE';
            $tableName = $this->statement()->escapeTableName($table);
            yield $this->nl("DROP $tableClause IF EXISTS $tableName;");
        }
        if ($tableType === 1) {
            $query = $this->statement()->removeDefiner($query);
        }
        yield $this->nl("$query;", 2);
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
            yield $this->nl("\nDELIMITER ;;\n{$triggers}\nDELIMITER ;");
        }
    }

    /**
     * @param TableDto $status
     * @param array $options
     *
     * @return Generator
     */
    private function dumpTable(TableDto $status, array $options): Generator
    {
        $dumpTable = $options['table'];
        $style = $dumpTable ? $this->options['table_style'] : '';
        $tableType = $this->engine()->isView($status) ? 2 : 1;
        yield from $this->dumpCreateTableOrView($status->name, $style, $tableType);

        $dumpData = $options['data'];
        if ($dumpData) {
            yield from $this->dumpTableData($status->name);
        }
        if ($this->toSql() && $this->options['triggers'] && $dumpTable) {
            yield from $this->dumpTableTriggers($status->name);
        }
    }

    /**
     * @param array<TableDto> $tableStatuses
     *
     * @return Generator
     */
    private function dumpForeignKeys(array $tableStatuses): Generator
    {
        $tables = array_filter($tableStatuses, $this->engine()->isTable(...));
        foreach ($tables as $status) {
            $queries = $this->statement()->getForeignKeyQueries($status);
            foreach ($queries as $query) {
                yield $this->nl($query);
            }
            if (count($queries) > 0) {
                yield "\n";
            }
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
            if ($this->toSql()) {
                yield "\n";
            }
            $options = $tableOptions[$status->name] ?? $tableOptions['*'];
            yield from $this->dumpTable($status, $options);
        }

        if ($this->engine()->pgsql() && $this->toSql()) {
            // Add FKs after creating tables (except in MySQL which uses SET FOREIGN_KEY_CHECKS=0)
            yield "\n";
            yield from $this->dumpForeignKeys($tableStatuses);
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
            $style = $this->options['table_style'];
            yield from $this->dumpCreateTableOrView($view->name, $style, 1);
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
            yield "\n"; // Empty line
            if (count($type->enums) === 0) {
                //! https://github.com/postgres/postgres/blob/REL_17_4/src/bin/pg_dump/pg_dump.c#L10846
                yield $this->nl("-- Could not export type {$type->name}");
                continue;
            }

            $typeName = $this->statement()->escapeId($type->name);
            if ($style !== 'DROP+CREATE') {
                yield $this->nl("DROP TYPE IF EXISTS $typeName;;");
            }
            $enums = implode("', '", $type->enums);
            yield $this->nl("CREATE TYPE $typeName AS ENUM ('$enums');");
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
            yield "\n"; // Empty line
            if ($style !== 'DROP+CREATE') {
                yield $this->nl("DROP {$routine->type} IF EXISTS $routineName;;");
            }
            yield $this->nl($create);
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
            $eventName = $this->statement()->escapeId($row['Name']);
            $sql = "SHOW CREATE EVENT $eventName";
            $create = $this->statement()->removeDefiner($this->engine()->columnValue($sql, 3));
            $this->statement()->setUtf8mb4($create);
            yield "\n"; // Empty line
            if ($style !== 'DROP+CREATE') {
                yield $this->nl("DROP EVENT IF EXISTS $eventName;;");
            }
            yield $this->nl("$create;;", 2);
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
        if ($this->toSql()) {
            $style = $this->options['db_style'] ?? '';
            if ($style !== '') {
                yield $this->nl($this->statement()->getUseDatabaseQuery($database, $style));
            }

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
            'version' => $this->version,
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

        if ($this->toSql()) {
            $this->timestamp = $this->engine()->columnValue('SELECT NOW()');
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

        if ($this->toSql()) {
            [
                'version' => $version,
                'driver' => $driver,
                'server' => $server,
                'sql' => $isSql,
                'data_style' => $dataStyle,
            ] = $this->getDatabaseExportHeaders();

            yield $this->nl("-- Jaxon DbAdmin {$version} {$driver} {$server} dump", 2);
            if ($isSql) {
                yield $this->nl("SET NAMES utf8;");
                yield $this->nl("SET time_zone = '+00:00';");
                yield $this->nl("SET foreign_key_checks = 0;");
                if ($dataStyle) {
                    yield $this->nl("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';");
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

        if ($this->toSql()) {
            yield $this->nl("-- $this->timestamp");
        }
    }
}

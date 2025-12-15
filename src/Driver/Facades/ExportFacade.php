<?php

namespace Lagdo\DbAdmin\Db\Driver\Facades;

use Lagdo\DbAdmin\Driver\Entity\FieldType;
use Lagdo\DbAdmin\Driver\Entity\RoutineEntity;
use Lagdo\DbAdmin\Driver\Entity\RoutineInfoEntity;
use Lagdo\Facades\Logger;
use Exception;

use function array_filter;
use function array_map;
use function count;
use function implode;
use function ksort;
use function preg_match;
use function rtrim;
use function str_replace;
use function trim;

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
        return $database === '' ? [
            'databases' => $this->getDatabases(),
            'options' => $this->getBaseOptions($database, $table),
            'prefixes' => [],
        ] : [
            'tables' => $this->getDbTables(),
            'options' => $this->getBaseOptions($database, $table),
            'prefixes' => [],
        ];
    }

    /**
     * @param array<FieldType> $params
     *
     * @return string
     */
    private function getRoutineParams(array $params): string
    {
        // From dump.inc.php create_routine()
        $params = array_filter($params, fn($param) => $param->name !== '');
        ksort($params); // enforce params order
        $regex = "~^(" . $this->driver->inout() . ")\$~";

        $params = array_map(function($param) use($regex) {
            $inout = preg_match($regex, $param->inout) ? "{$param->inout} " : '';
            return $inout . $this->driver->escapeId($param->name) .
                $this->driver->processType($param, 'CHARACTER SET');
        },$params);
        return implode(', ', $params);
    }

    /**
     * Generate SQL query for creating routine
     *
     * @param RoutineEntity $routine
     * @param RoutineInfoEntity $routineInfo
     *
     * @return string
     */
    private function getRoutineQuery(RoutineEntity $routine, RoutineInfoEntity $routineInfo): string
    {
        // From dump.inc.php create_routine()
        $routineName = $this->driver->escapeId(trim($routine->name));
        $routineParams = $this->getRoutineParams($routineInfo->params);
        $routineReturns = $routine->type !== 'FUNCTION' ? '' :
            ' RETURNS' . $this->driver->processType($routineInfo->return, 'CHARACTER SET');
        $routineLanguage = $routineInfo->language ? " LANGUAGE {$routineInfo->language}" : '';
        $definition = rtrim($routineInfo->definition, ';');
        $routineDefinition = $this->driver->jush() !== 'pgsql' ? "\n$definition;" :
            ' AS ' . $this->driver->quote($definition);

        return "CREATE {$routine->type} $routineName ($routineParams)" .
            "{$routineReturns}{$routineLanguage}{$routineDefinition};";
    }

    /**
     * Dump types in the connected database
     *
     * @return void
     */
    private function dumpTypes()
    {
        if (!$this->options['types']) {
            return;
        }

        // From dump.inc.php
        $style = $this->options['db_style'];
        foreach ($this->driver->userTypes(true) as $type) {
            $this->queries[] = ''; // Empty line
            if (count($type->enums) === 0) {
                //! https://github.com/postgres/postgres/blob/REL_17_4/src/bin/pg_dump/pg_dump.c#L10846
                $this->queries[] = "-- Could not export type {$type->name}";
                continue;
            }

            $typeName = $this->driver->escapeId($type->name);
            if ($style !== 'DROP+CREATE') {
                $this->queries[] = "DROP TYPE IF EXISTS $typeName;;";
            }
            $enums = implode("', '", $type->enums);
            $this->queries[] = "CREATE TYPE $typeName AS ENUM ('$enums');";
        }
    }

    /**
     * Dump routines in the connected database
     *
     * @return void
     */
    private function dumpRoutines()
    {
        if (!$this->options['routines']) {
            return;
        }

        // From dump.inc.php
        $style = $this->options['db_style'];
        foreach ($this->driver->routines() as $routine) {
            $routineName = $this->driver->escapeId(trim($routine->name));
            $routineInfo = $this->driver->routine($routine->specificName, $routine->type);
            if ($routineInfo === null) {
                continue;
            }

            $create = $this->getRoutineQuery($routine, $routineInfo);
            $this->driver->setUtf8mb4($create);
            $this->queries[] = ''; // Empty line
            if ($style !== 'DROP+CREATE') {
                $this->queries[] = "DROP {$routine->type} IF EXISTS $routineName;;";
            }
            $this->queries[] = $create;
        }
    }

    /**
     * Dump events in the connected database
     *
     * @return void
     */
    private function dumpEvents()
    {
        if (!$this->options['events']) {
            return;
        }

        // From dump.inc.php
        $style = $this->options['db_style'];
        foreach ($this->driver->rows('SHOW EVENTS') as $row) {
            $sql = 'SHOW CREATE EVENT ' . $this->driver->escapeId($row['Name']);
            $create = $this->driver->removeDefiner($this->driver->result($sql, 3));
            $this->driver->setUtf8mb4($create);
            $this->queries[] = ''; // Empty line
            if ($style !== 'DROP+CREATE') {
                $this->queries[] = 'DROP EVENT IF EXISTS ' . $this->driver->escapeId($row['Name']) . ';;';
            }
            $this->queries[] = "$create;;\n";
        }
    }

    /**
     * @param string $database
     *
     * @return void
     */
    private function dumpUseDatabaseQuery(string $database)
    {
        $style = $this->options['db_style'];
        if ($style === '' || !preg_match('~sql~', $this->options['format'])) {
            return;
        }

        $this->queries[] = $this->driver->getUseDatabaseQuery($database, $style);
    }

    /**
     * @param string $database
     * @param array $tableOptions
     *
     * @return void
     */
    private function dumpDatabase(string $database, array $tableOptions)
    {
        $this->driver->openConnection($database); // New connection
        $this->dumpUseDatabaseQuery($database);

        if ($this->options['to_sql']) {
            $this->dumpTypes();
            $this->dumpRoutines();
            $this->dumpEvents();
        }

        if (!$this->options['table_style'] && !$this->options['data_style']) {
            return;
        }

        $statuses = array_filter($this->driver->tableStatuses(true), fn($status) =>
            isset($tableOptions['*']) || isset($tableOptions[$status->name]));
        $this->dumpTables($statuses, $tableOptions);
        // Dump the views after all the tables
        $this->dumpViews($statuses);
    }

    /**
     * @return array
     */
    private function getDatabaseExportHeaders(): array
    {
        $headers = [
            'version' => $this->driver->version(),
            'driver' => $this->driver->name(),
            'server' => str_replace("\n", ' ', $this->driver->serverInfo()),
            'sql' => false,
            'data_style' => false,
        ];
        if ($this->driver->jush() === 'sql') {
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
     * @param array  $options       The export options
     *
     * @return array|string
     */
    public function exportDatabases(array $databases, array $options): array|string
    {
        // From dump.inc.php
        // $tables = array_flip($options['tables']) + array_flip($options['data']);
        // $ext = dump_headers((count($tables) == 1 ? key($tables) : DB), (DB == '' || count($tables) > 1));
        $this->options = $options;
        // Export to SQL format (renamed from is_sql to to_sql).
        $this->options['to_sql'] = preg_match('~sql~', $options['format']) === 1;

        $headers = !$this->options['to_sql'] ? null : $this->getDatabaseExportHeaders();

        foreach ($databases as $database => $tables) {
            try {
                $this->dumpDatabase($database, $tables);
            }
            catch (Exception $e) {
                return $e->getMessage();
            }
        }

        if ($this->options['to_sql']) {
            $this->queries[] = '-- ' . $this->driver->result('SELECT NOW()');
        }

        return [
            'headers' => $headers,
            'queries' => $this->queries,
        ];
    }
}

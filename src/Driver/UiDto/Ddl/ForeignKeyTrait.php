<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Ddl;

use Lagdo\DbAdmin\Driver\EngineInterface;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableDto;

use function array_combine;
use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function count;
use function str_replace;

trait ForeignKeyTrait
{
    /**
     * @return EngineInterface
     */
    abstract protected function engine(): EngineInterface;

    /**
     * @param TableDto $tableStatus
     *
     * @return ReferencableDto
     */
    private function makeReferencableDto(TableDto $tableStatus): ReferencableDto
    {
        $columnFilter = fn(ColumnDto $column) => $column->primary;
        $columns = array_values(array_filter($tableStatus->columns(), $columnFilter));
        return new ReferencableDto($tableStatus->name, $columns[0]);
    }

    /**
     * @return array<ReferencableDto>
     */
    private function getReferencableColumns(): array
    {
        $columnFilter = fn(ColumnDto $column) => $column->primary;
        $tableFilter = fn(TableDto $tableStatus) =>
            $this->engine()->supportForeignKeys($tableStatus) &&
            count(array_filter($tableStatus->columns(), $columnFilter)) === 1;
        $tables = $this->engine()->tableStatuses(true);
        $tables = array_filter($tables, $tableFilter, ARRAY_FILTER_USE_BOTH);

        $referencables = array_map($this->makeReferencableDto(...), $tables);
        $keys = array_map(fn(ReferencableDto $referencable) =>
            "{$referencable->table}::{$referencable->column->name}", $referencables);
        return array_combine($keys, $referencables);
    }

    /**
     * @param array<ReferencableDto> $referencables
     *
     * @return array<string, string>
     */
    private function formatReferencableColumns(array $referencables): array
    {
        return array_map(fn(ReferencableDto $referencable) =>
            "{$referencable->table}({$referencable->column->name})", $referencables);
    }

    /**
     * Get foreign keys
     *
     * @param string $table
     *
     * @return array
     */
    private function getForeignKeys(string $table = ''): array
    {
        $columns = $this->getReferencableColumns();
        $replace = fn(string $name) => str_replace("`", "``", $name);
        $convertName = fn(ColumnDto $column, string $tableName) =>
            $replace($tableName) . "`" . $replace($column->name); // not escapeId() - used in JS
        $columnNames =  array_keys($columns);
        $foreignKeys = array_map($convertName, $columns, $columnNames);

        return array_combine($foreignKeys, array_map(fn($name) => "$name()", $columnNames));
    }
}

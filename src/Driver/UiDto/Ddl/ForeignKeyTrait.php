<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Ddl;

use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnAction;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDdDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\ForeignKeyDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableDdDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableDto;

use function array_filter;
use function array_flip;
use function array_map;
use function array_values;
use function count;
use function is_string;
use function str_replace;

trait ForeignKeyTrait
{
    /**
     * @param string $table
     *
     * @return array<ColumnDto>
     */
    private function getReferencableColumns(string $table): array
    {
        // From editing.inc.php, function referencable_primary()

        $filter = fn(TableDto $tableStatus, string $tableName) =>
            $tableName != $table && $this->engine()->supportForeignKeys($tableStatus);
        $tables = $this->engine()->tableStatuses(true);
        $tables = array_filter($tables, $filter, ARRAY_FILTER_USE_BOTH);

        $filter = fn(ColumnDto $column) => $column->primary;
        $primaryColumns = array_map(fn(TableDto $tableStatus) =>
            array_values(array_filter($tableStatus->columns(), $filter)), $tables);

        // Remove multi column primary keys
        $filter = fn(array $columns) => count($columns) === 1;
        $primaryColumns = array_filter($primaryColumns, $filter);

        return array_map(fn(array $columns) => $columns[0], $primaryColumns);
    }

    /**
     * Get foreign keys
     *
     * @param TableDdDto|string $table
     *
     * @return array
     */
    private function getForeignKeys(TableDdDto|string $table = ''): array
    {
        $columns = is_string($table) ?
            $this->getReferencableColumns($table) :
            $table->getReferencableColumns();

        $replace = fn(string $name) => str_replace("`", "``", $name);
        $convertName = fn(ColumnDto $column, string $tableName) =>
            $replace($tableName) . "`" . $replace($column->name); // not escapeId() - used in JS
        $foreignKeys = array_map($convertName, $columns, array_keys($columns));

        return array_flip($foreignKeys);
    }

    /**
     * @param TableDdDto $table
     * @param ColumnAction $action
     * @param ColumnFormDto $input
     * @param array $foreignKeys
     *
     * @return ColumnDdDto
     */
    private function makeColumnInput(TableDdDto $table, ColumnAction $action,
        ColumnFormDto $input, array $foreignKeys): ColumnDdDto
    {
        $values = $input->values();

        //! can collide with user defined type
        $foreignKey = $foreignKeys[$values->type] ?? '';
        $typeColumn = $table->getReferencableColumns()[$foreignKey] ?? null;
        if ($typeColumn !== null) {
            $fkColumn = new ForeignKeyDto();
            $fkColumn->table = $foreignKey;
            $fkColumn->source = [$values->name];
            $fkColumn->target = [$typeColumn->name];
            $fkColumn->onDelete = $values->onDelete;

            $table->foreignKeys[$values->name] = $fkColumn;
        }

        $column = new ColumnDdDto($input->column, $typeColumn);
        foreach ($input->attributes() as $attr) {
            $column->$attr = $values->$attr;
        }
        if ($values->generated === '') {
            $column->default = null;
        }
        if (!$values->setComment) {
            $column->comment = null;
        }
        if ($action === ColumnAction::ADD && $column->autoIncrement) {
            $column->type = $this->statement()->getAutoIncrementType($column->type);
        }

        return $column;
    }
}

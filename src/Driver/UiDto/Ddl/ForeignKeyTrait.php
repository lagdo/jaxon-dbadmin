<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Ddl;

use Lagdo\DbAdmin\Driver\Sql\Dto\AbstractTableDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnInputDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\ForeignKeyDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;

use function str_replace;

trait ForeignKeyTrait
{
    /**
     * @var string
     */
    private string $tableName;

    /**
     * @var array|null
     */
    private array|null $referencableColumns = null;

    /**
     * @param string $table
     *
     * @return array<ColumnDto>
     */
    private function getReferencableColumns(string $table): array
    {
        // From editing.inc.php, function referencable_primary()
        $columns = []; // table_name => column
        foreach ($this->engine()->tableStatuses(true) as $tableName => $tableStatus) {
            if ($tableName != $table && $this->engine()->supportForeignKeys($tableStatus)) {
                $tableColumns = $this->engine()->columns($tableName);
                foreach ($tableColumns as $column) {
                    if ($column->primary) {
                        if (isset($columns[$tableName])) { // multi column primary key
                            unset($columns[$tableName]);
                            break;
                        }
                        $columns[$tableName] = $column;
                    }
                }
            }
        }
        return $columns;
    }

    /**
     * @param string $table
     *
     * @return array<ColumnDto>
     */
    private function referencableColumns(string $table = ''): array
    {
        return $this->referencableColumns ??= $this->getReferencableColumns($table);
    }

    /**
     * Get foreign keys
     *
     * @param string $table     The table name
     *
     * @return array
     */
    protected function getForeignKeys(string $table = ''): array
    {
        $foreignKeys = [];
        foreach ($this->referencableColumns($table) as $tableName => $column) {
            $name = str_replace("`", "``", $tableName) . "`" .
                str_replace("`", "``", $column->name);
            // not escapeId() - used in JS
            $foreignKeys[$name] = $tableName;
        }

        return $foreignKeys;
    }

    /**
     * @param AbstractTableDto $table
     * @param ColumnDdDto $input
     * @param array $foreignKeys
     *
     * @return ColumnInputDto
     */
    private function makeColumnInput(AbstractTableDto $table,
        ColumnDdDto $input, array $foreignKeys): ColumnInputDto
    {
        $this->referencableColumns ??= $this->getReferencableColumns($this->tableName);

        $column = $input->makeColumn();
        $foreignKey = $foreignKeys[$column->type] ?? null;
        //! can collide with user defined type
        $typeColumn = $foreignKey !== null ? $this->referencableColumns[$foreignKey] : $column;

        if ($foreignKey !== null) {
            $fkColumn = new ForeignKeyDto();
            $fkColumn->table = $foreignKey;
            $fkColumn->source = [$column->name];
            $fkColumn->target = [$typeColumn->name];
            $fkColumn->onDelete = $column->onDelete;
            $table->foreignKeys[$column->name] = $fkColumn;
        }

        return $this->statement()->makeColumnInput($column, $typeColumn);
    }
}

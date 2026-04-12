<?php

namespace Lagdo\DbAdmin\Db\UiData\Ddl;

use Lagdo\DbAdmin\Db\Driver\Proxy\AbstractProxy;
use Lagdo\DbAdmin\Driver\Sql\Dto\ForeignKeyDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableAlterDto;

use function array_filter;
use function count;

class TableAlter extends AbstractProxy
{
    use ForeignKeyTrait;

    /**
     * @param TableAlterDto $table
     * @param array<ColumnInputDto> $columns
     * 
     * @return TableAlterDto
     */
    public function makeDto(TableAlterDto $table, array $columns): TableAlterDto
    {
        // From create.inc.php
        $this->getForeignKeys($table->name);

        // Auto increment
        $aiCount = count(array_filter($columns, fn(ColumnInputDto $column) =>
            $column->field->autoIncrement));
        if ($aiCount > 1) {
            $table->error = $this->utils()->lang('Only one auto-increment field is allowed.');
            return $table;
        }

        // Todo: move fields up and down

        $referencableFields = $this->referencableFields($table->name);
        // $after = " FIRST";

        $table->clearColumns();
        foreach ($columns as $column) {
            if ($column->unchanged()) {
                continue;
            }
            if ($column->dropped()) {
                $table->droppedColumns[] = $column->name;
                continue;
            }

            $inputField = $column->inputField();
            $foreignKey = $this->foreignKeys[$inputField->type] ?? null;
            //! can collide with user defined type
            $typeField = $foreignKey !== null ? $referencableFields[$foreignKey] : $inputField;

            $input = $this->statement()->getFieldClauses($inputField, $typeField);
            // $input->after = $after;

            if ($foreignKey !== null) {
                $fkField = new ForeignKeyDto();
                $fkField->table = $foreignKey;
                $fkField->source = [$inputField->name];
                $fkField->target = [$typeField->name];
                $fkField->onDelete = $inputField->onDelete;
                $table->foreignKeys[$inputField->name] = $fkField;
            }

            $column->added() ? $table->addedColumns[] = $input :
                $table->changedColumns[$column->name] = $input;
            // $after = " AFTER " . $this->statement()->escapeId($inputField->name);
        }

        return $table;
    }
}

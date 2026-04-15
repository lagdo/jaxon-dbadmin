<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Ddl;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Driver\Sql\Dto\ForeignKeyDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableCreateDto;

use function array_filter;
use function count;

class TableCreate extends AbstractDriverProxy
{
    use ForeignKeyTrait;

    /**
     * @param TableCreateDto $table
     * @param array<ColumnInputDto> $columns
     * 
     * @return TableCreateDto
     */
    public function makeDto(TableCreateDto $table, array $columns): TableCreateDto
    {
        // From create.inc.php
        $this->getForeignKeys();

        // Auto increment
        $aiCount = count(array_filter($columns, fn($column) => $column->field->autoIncrement));
        if ($aiCount > 1) {
            $table->error = $this->utils()->lang('Only one auto-increment field is allowed.');
            return $table;
        }

        $referencableFields = $this->referencableFields();
        // $after = " FIRST";

        $table->clearColumns();
        foreach ($columns as $column) {
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

            $table->columns[] = $input;
            // $after = " AFTER " . $this->statement()->escapeId($inputField->name);
        }

        return $table;
    }
}

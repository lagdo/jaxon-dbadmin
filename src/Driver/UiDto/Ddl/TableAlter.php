<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Ddl;

use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnAction;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableAlterDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;

use function array_filter;

class TableAlter extends AbstractDriverProxy
{
    use ForeignKeyTrait;

    /**
     * @param TableAlterDto $table
     * @param array<ColumnFormDto> $inputs
     * 
     * @return TableAlterDto
     */
    public function makeDto(TableAlterDto $table, array $inputs): TableAlterDto
    {
        // From create.inc.php
        $foreignKeys = $this->getForeignKeys($table);

        // Todo: move fields up and down
        // $after = " FIRST";

        $table->clearColumns();
        $table->columns[ColumnAction::ADD->value] = array_map( fn(ColumnFormDto $input) =>
            $this->makeColumnInput($table, ColumnAction::ADD, $input, $foreignKeys),
            array_filter($inputs, fn(ColumnFormDto $input) => $input->added()));
        $table->columns[ColumnAction::EDIT->value] = array_map( fn(ColumnFormDto $input) =>
            $this->makeColumnInput($table, ColumnAction::EDIT, $input, $foreignKeys),
            array_filter($inputs, fn(ColumnFormDto $input) => $input->changed()));
        $table->columns[ColumnAction::DROP->value] = array_map(
            fn(ColumnFormDto $input) => $input->column->name,
            array_filter($inputs, fn(ColumnFormDto $input) => $input->dropped()));

        // Auto increment
        if ($table->autoIncrementColumnCount() > 1) {
            $table->error = $this->utils()->lang('Only one auto-increment column is allowed.');
        }

        return $table;
    }
}

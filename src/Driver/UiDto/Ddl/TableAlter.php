<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Ddl;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableAlterDto;

use function array_filter;
use function count;

class TableAlter extends AbstractDriverProxy
{
    use ForeignKeyTrait;

    /**
     * @param TableAlterDto $table
     * @param array<ColumnDdDto> $inputs
     * 
     * @return TableAlterDto
     */
    public function makeDto(TableAlterDto $table, array $inputs): TableAlterDto
    {
        // From create.inc.php
        $foreignKeys = $this->getForeignKeys($table);

        // Auto increment
        $aiCount = count(array_filter($inputs, fn(ColumnDdDto $input) =>
            $input->column->autoIncrement));
        if ($aiCount > 1) {
            $table->error = $this->utils()->lang('Only one auto-increment column is allowed.');
            return $table;
        }

        // Todo: move fields up and down

        // $after = " FIRST";

        $table->clearColumns();
        $table->columns['added'] = array_map(
            fn(ColumnDdDto $input) => $this->makeColumnInput($table, $input, $foreignKeys),
            array_filter($inputs, fn(ColumnDdDto $input) => $input->added()));
        $table->columns['edited'] = array_map(
            fn(ColumnDdDto $input) => $this->makeColumnInput($table, $input, $foreignKeys),
            array_filter($inputs, fn(ColumnDdDto $input) => $input->changed()));
        $table->columns['dropped'] = array_map(
            fn(ColumnDdDto $input) => $input->column->name,
            array_filter($inputs, fn(ColumnDdDto $input) => $input->dropped()));

        return $table;
    }
}

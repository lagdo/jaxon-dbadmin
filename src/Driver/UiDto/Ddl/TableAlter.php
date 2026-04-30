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
     * @param array<DdInputDto> $inputs
     * 
     * @return TableAlterDto
     */
    public function makeDto(TableAlterDto $table, array $inputs): TableAlterDto
    {
        // From create.inc.php
        $this->getForeignKeys($table->name);

        // Auto increment
        $aiCount = count(array_filter($inputs, fn(DdInputDto $input) =>
            $input->column->autoIncrement));
        if ($aiCount > 1) {
            $table->error = $this->utils()->lang('Only one auto-increment column is allowed.');
            return $table;
        }

        // Todo: move fields up and down

        // Required to be able to get the referencable columns.
        $this->tableName = '';

        // $after = " FIRST";

        $table->clearColumns();
        $table->inputs['added'] = array_map(
            fn(DdInputDto $input) => $this->makeColumnInput($table, $input),
            array_filter($inputs, fn(DdInputDto $input) => $input->added()));
        $table->inputs['edited'] = array_map(
            fn(DdInputDto $input) => $this->makeColumnInput($table, $input),
            array_filter($inputs, fn(DdInputDto $input) => $input->changed()));
        $table->droppedColumns = array_map(
            fn(DdInputDto $input) => $input->column->name,
            array_filter($inputs, fn(DdInputDto $input) => $input->dropped()));

        return $table;
    }
}

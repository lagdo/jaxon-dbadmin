<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Ddl;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableCreateDto;

use function array_filter;
use function count;

class TableCreate extends AbstractDriverProxy
{
    use ForeignKeyTrait;

    /**
     * @param TableCreateDto $table
     * @param array<DdInputDto> $inputs
     * 
     * @return TableCreateDto
     */
    public function makeDto(TableCreateDto $table, array $inputs): TableCreateDto
    {
        // From create.inc.php
        $this->getForeignKeys();

        // Auto increment
        $aiColumns = array_filter($inputs, fn($input) => $input->column->autoIncrement);
        if (count($aiColumns) > 1) {
            $table->error = $this->utils()->lang('Only one auto-increment column is allowed.');
            return $table;
        }

        // Required to be able to get the referencable columns.
        $this->tableName = '';

        // $after = " FIRST";

        $table->clearColumns();
        $table->inputs['added'] = array_map(fn(DdInputDto $input) =>
            $this->makeColumnInput($table, $input), $inputs);

        return $table;
    }
}

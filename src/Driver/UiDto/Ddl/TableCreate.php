<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Ddl;

use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnAction;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableCreateDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;

class TableCreate extends AbstractDriverProxy
{
    use ForeignKeyTrait;

    /**
     * @param TableCreateDto $table
     * @param array<ColumnDdDto> $inputs
     * 
     * @return TableCreateDto
     */
    public function makeDto(TableCreateDto $table, array $inputs): TableCreateDto
    {
        // From create.inc.php
        $foreignKeys = $this->getForeignKeys($table);

        // $after = " FIRST";

        $table->clearColumns();
        $table->columns[ColumnAction::ADD->value] = array_map(fn(ColumnDdDto $input) =>
            $this->makeColumnInput($table, ColumnAction::ADD, $input, $foreignKeys), $inputs);

        // Auto increment
        if ($table->autoIncrementColumnCount() > 1) {
            $table->error = $this->utils()->lang('Only one auto-increment column is allowed.');
        }

        return $table;
    }
}

<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Ddl;

use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnAction;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableCreateDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;

use function array_filter;
use function count;

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

        // Auto increment
        $aiColumns = array_filter($inputs, fn($input) => $input->column->autoIncrement);
        if (count($aiColumns) > 1) {
            $table->error = $this->utils()->lang('Only one auto-increment column is allowed.');
            return $table;
        }

        // $after = " FIRST";

        $table->clearColumns();
        $table->columns[ColumnAction::ADD->value] = array_map(fn(ColumnDdDto $input) =>
            $this->makeColumnInput($table, $input, $foreignKeys), $inputs);

        return $table;
    }
}

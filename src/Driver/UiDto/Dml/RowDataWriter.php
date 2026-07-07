<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dml;

use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\ForeignKeyDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\DescriptionColumnTrait;

use function array_filter;
use function array_map;
use function count;

/**
 * Reads data from the database for the row insert and update user forms.
 */
class RowDataWriter extends AbstractDriverProxy
{
    use DescriptionColumnTrait;

    /**
     * @var ColumnValue
     */
    private ColumnValue $columnValue;

    /**
     * @var ColumnInput
     */
    private ColumnInput $columnInput;

    /**
     * @var bool|null
     */
    private bool|null $autofocus;

    /**
     * @return self
     */
    public function init(): self
    {
        $this->columnValue = new ColumnValue($this);
        $this->columnInput = new ColumnInput($this);
        return $this;
    }

    /**
     * @param string $action
     * @param string $operation
     *
     * @return self
     */
    public function action(string $action, string $operation): self
    {
        $this->columnValue->action($action, $operation);
        $this->columnInput->action($action, $operation);
        return $this;
    }

    /**
     * @param ForeignKeyDto $foreignKey
     *
     * @return ForeignKeyDmDto|null
     */
    private function makeForeignKeyDto(ForeignKeyDto $foreignKey): ForeignKeyDmDto|null
    {
        if (count($foreignKey->source) !== 1) {
            return null;
        }

        $labelColumn = $this->getDescriptionColumn($foreignKey->table);
        if ($labelColumn === '') {
            return null;
        }

        return new ForeignKeyDmDto($foreignKey->table, $foreignKey->target[0], $labelColumn);
    }

    /**
     * @param string $table
     * @param array<ColumnDto> $columns
     * @param array|null $rowData
     *
     * @return array<ColumnDmDto>
     */
    public function getInputValues(string $table, array $columns, array|null $rowData = null): array
    {
        // From html.inc.php (function edit_form($table, $columns, $rowData, $update))
        $this->autofocus = false;

        $inputColumns = array_map(function(ColumnDto $column) use($rowData) {
            $input = $this->columnValue->getColumnInputValues($column, $rowData);
            if ($this->autofocus !== false) {
                $this->autofocus = match(true) {
                    $column->autoIncrement => null,
                    $input->function === 'now' => null,
                    $input->function === 'uuid' => null,
                    default => true,
                };
            }

            // Format the data columns for the user input form.
            $this->columnInput->setColumnInputValues($input, $this->autofocus);
            if ($this->autofocus) {
                $this->autofocus = false;
            }

            return $input;
        }, $columns);

        foreach ($this->engine()->foreignKeys($table) as $foreignKey) {
            $source = $foreignKey->source[0];
            if (isset($inputColumns[$source])) {
                $inputColumns[$source]->foreignKey = $this->makeForeignKeyDto($foreignKey);
            }
        }

        return $inputColumns;
    }
}

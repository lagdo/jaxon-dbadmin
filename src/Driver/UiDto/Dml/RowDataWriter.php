<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dml;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;

use function array_map;
use function array_keys;

/**
 * Reads data from the database for the row insert and update user forms.
 */
class RowDataWriter extends AbstractDriverProxy
{
    /**
     * @var string
     */
    private string $action;

    /**
     * @var string
     */
    private string $operation;

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
     * @param string $action
     * @param string $operation
     * @param ColumnValue $columnValue
     * @param ColumnInput $columnInput
     *
     * @return self
     */
    public function init(string $action, string $operation,
        ColumnValue $columnValue, ColumnInput $columnInput): self
    {
        $this->action = $action;
        $this->operation = $operation;
        $this->columnValue = $columnValue;
        $this->columnInput = $columnInput;
        return $this;
    }

    /**
     * @param array $values
     * @param array<string, ColumnDto> $columns
     * @param array $options
     *
     * @return array
     */
    public function getUpdatedRow(array $values, array $columns, array $options): array
    {
        $textLength = $options['select']['length'];
        return array_map(function($value, $columnName) use($columns, $textLength) {
            $column = $columns[$columnName];
            $value = $this->engine()->convertValue($value, $column);
            return $this->pageUi()->getColumnValue($column, $textLength, $value);
        }, $values, array_keys($values));
    }

    /**
     * @param array<ColumnDto> $columns
     * @param array|null $rowData
     *
     * @return array<DmInputDto>
     */
    public function getInputValues(array $columns, array|null $rowData = null): array
    {
        // From html.inc.php (function edit_form($table, $columns, $rowData, $update))
        $this->autofocus = $this->action !== 'save';

        return array_map(function(ColumnDto $column) use($rowData) {
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
    }
}

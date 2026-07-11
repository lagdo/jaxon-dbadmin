<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dml;

use Jaxon\Config\Config;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\ForeignColumnTrait;

use function count;
use function array_map;

/**
 * Reads data from the database for the row insert and update user forms.
 */
class RowDataWriter extends AbstractDriverProxy
{
    use ForeignColumnTrait;

    /**
     * @var Config
     */
    private Config $packageConfig;

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
     * @return Config
     */
    protected function config(): Config
    {
        return $this->packageConfig;
    }

    /**
     * @return static
     */
    public function init(Config $packageConfig): static
    {
        $this->packageConfig = $packageConfig;
        $this->columnValue = new ColumnValue($this);
        $this->columnInput = new ColumnInput($this);
        return $this;
    }

    /**
     * @param string $action
     * @param string $operation
     *
     * @return static
     */
    public function action(string $action, string $operation): static
    {
        $this->columnValue->action($action, $operation);
        $this->columnInput->action($action, $operation);
        return $this;
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
            $source = $foreignKey->source[0] ?? '';
            if (count($foreignKey->source) === 1 && isset($inputColumns[$source])) {
                $foreignColumn = $this->getForeignKeyColumn($foreignKey);
                $isSearchable = ($foreignColumn?->search ?? null) !== null;
                $inputColumns[$source]->foreignKey = $isSearchable ? $foreignKey : null;
            }
        }

        return $inputColumns;
    }
}

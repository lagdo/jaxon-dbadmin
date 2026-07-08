<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dml;

use Jaxon\Config\Config;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\ForeignKeyDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\ForeignColumnTrait;

use function array_map;
use function count;

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
     * @param ForeignKeyDto $foreignKey
     *
     * @return ForeignKeyDto|null
     */
    private function searchableForeignKey(ForeignKeyDto $foreignKey): ForeignKeyDto|null
    {
        if (count($foreignKey->source) !== 1) {
            return null;
        }

        // Make sure the referenced column is setup, and a filter clause is defined.
        [$idColumn, , $filter] = $this->getForeignKeyColumn($foreignKey);
        return $idColumn === '' || $filter === null ? null : $foreignKey;
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
            $source = $foreignKey->source[0];
            if (isset($inputColumns[$source])) {
                $inputColumns[$source]->foreignKey = $this->searchableForeignKey($foreignKey);
            }
        }

        return $inputColumns;
    }
}

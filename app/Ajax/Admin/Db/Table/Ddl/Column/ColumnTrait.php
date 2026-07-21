<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\Column;

use Lagdo\DbAdmin\Support\Driver\DriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\ColumnFormDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\TableFormDto;

use function array_combine;
use function array_keys;
use function array_map;

trait ColumnTrait
{
    /**
     * The database table data.
     *
     * @var TableFormDto|null
     */
    private TableFormDto|null $metadata = null;

    /**
     * @return DriverProxy
     */
    abstract protected function driver(): DriverProxy;

    /**
     * @return TableFormDto
     */
    protected function metadata(): TableFormDto
    {
        return $this->metadata ??= $this->driver()->getTableMetadata($this->getCurrentTable());
    }

    /**
     * @return TableFormDto
     */
    protected function tableDto(): TableFormDto
    {
        return $this->metadata();
    }

    /**
     * @param array|null $values
     *
     * @return ColumnFormDto
     */
    protected function newColumnInput(array|null $values = null): ColumnFormDto
    {
        return $this->driver()->newColumnInput($values);
    }

    /**
     * @param string $columnId
     * @param array|null $values
     *
     * @return ColumnFormDto|null
     */
    protected function getColumnInput(string $columnId, array|null $values = null): ColumnFormDto|null
    {
        if ($values === null) {
            $inputs = $this->getTableBag('columns', []);
            $values = $inputs[$columnId] ?? null;
        }
        if ($values === null) {
            return null;
        }

        $columns = $this->tableDto()->columns;
        $column = ColumnFormDto::columnIsAdded($values) ?
            // Added column => empty column
            $this->driver()->newColumnInput() :
            // Existing column => check the metadata
            ($columns[$values['name']] ?? null);
        // Combine the data from the database with the data from the databag.
        return $column?->updateValues($values) ?? null;
    }

    /**
     * @return array<ColumnFormDto>
     */
    protected function getColumnInputs(): array
    {
        $inputs = $this->getTableBag('columns', []);
        $keys = array_keys($inputs);
        $inputs = array_map($this->getColumnInput(...), $keys, $inputs);
        return array_combine($keys, $inputs);
    }
}

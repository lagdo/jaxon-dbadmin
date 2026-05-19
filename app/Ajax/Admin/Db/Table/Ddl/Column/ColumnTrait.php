<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\Column;

use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\ColumnFormDto;

trait ColumnTrait
{
    /**
     * The database table data.
     *
     * @var array|null
     */
    private array|null $metadata = null;

    /**
     * The columns input data stored in the client.
     *
     * @var array|null
     */
    private array|null $inputValues = null;

    /**
     * @return array
     */
    protected function metadata(): array
    {
        return $this->metadata ??= $this->db()->getTableMetadata($this->getCurrentTable());
    }

    /**
     * @return array
     */
    protected function inputValues(): array
    {
        return $this->inputValues ??= $this->getTableBag('columns', []);
    }

    /**
     * @param array|null $values
     *
     * @return ColumnFormDto
     */
    protected function newColumnInput(array|null $values = null): ColumnFormDto
    {
        return $this->db()->newColumnInput($values);
    }

    /**
     * @param string $columnId
     *
     * @return ColumnFormDto|null
     */
    protected function getColumnInput(string $columnId): ColumnFormDto|null
    {
        $values = $this->inputValues()[$columnId] ?? null;
        if ($values === null) {
            return null;
        }

        $column = ColumnFormDto::columnIsAdded($values) ?
            // Added column => empty column
            $this->db()->newColumnInput() :
            // Existing column => check the metadata
            ($this->metadata()['columns'][$values['name']] ?? null);
        // Combine the data from the database with the data from the databag.
        return $column?->updateValues($values) ?? null;
    }

    /**
     * @return array<ColumnFormDto>
     */
    protected function getColumnInputs(): array
    {
        $inputs = [];
        foreach ($this->inputValues() as $columnId => $_) {
            $inputs[$columnId] = $this->getColumnInput($columnId);
        }
        return $inputs;
    }
}

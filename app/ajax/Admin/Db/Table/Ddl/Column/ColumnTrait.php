<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Column;

use Lagdo\DbAdmin\Db\UiData\Ddl\ColumnInputDto;

trait ColumnTrait
{
    /**
     * The database table data.
     *
     * @var array|null
     */
    private array|null $metadata = null;

    /**
     * The columns data stored in the client.
     *
     * @var array|null
     */
    private array|null $columnInputs = null;

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
    protected function columnInputs(): array
    {
        return $this->columnInputs ??= $this->getTableBag('columns', []);
    }

    /**
     * @return ColumnInputDto
     */
    protected function getEmptyColumn(): ColumnInputDto
    {
        return new ColumnInputDto($this->db()->getTableField());
    }

    /**
     * @param string $columnId
     *
     * @return ColumnInputDto|null
     */
    protected function getFieldColumn(string $columnId): ColumnInputDto|null
    {
        $columnInput = $this->columnInputs()[$columnId] ?? null;
        if ($columnInput === null) {
            return null;
        }

        $field = ColumnInputDto::columnIsAdded($columnInput) ?
            // Added column => empty field
            $this->db()->getTableField() :
            // Existing column => check the metadata
            ($this->metadata()['fields'][$columnInput['name']] ?? null);
        // Fill the data from the database with the data from the databag.
        return $field === null ? null : ColumnInputDto::newColumn($field, $columnInput);
    }

    /**
     * @return array<ColumnInputDto>
     */
    protected function getTableColumns(): array
    {
        $columns = [];
        foreach ($this->columnInputs() as $columnId => $_) {
            $columns[$columnId] = $this->getFieldColumn($columnId);
        }
        return $columns;
    }
}

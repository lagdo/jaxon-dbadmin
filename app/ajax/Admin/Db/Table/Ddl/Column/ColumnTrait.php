<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Column;

use Lagdo\DbAdmin\Db\Page\Ddl\ColumnEntity;

use function array_map;

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
    private array|null $columns = null;

    /**
     * @return array
     */
    protected function metadata(): array
    {
        return $this->metadata ??= $this->db()->getTableData($this->getTableName());
    }

    /**
     * @return array
     */
    protected function columns(): array
    {
        return $this->columns ??= $this->bag('dbadmin.table')->get('columns', []);
    }

    /**
     * @return ColumnEntity
     */
    protected function getEmptyColumn(): ColumnEntity
    {
        return new ColumnEntity($this->db()->getTableField());
    }

    /**
     * @param string $fieldName
     *
     * @return ColumnEntity|null
     */
    protected function getFieldColumn(string $fieldName): ColumnEntity|null
    {
        $column = $this->columns()[$fieldName] ?? null;
        if ($column === null) {
            return null;
        }

        $field = $column['status'] === 'added' ?
            // New column => empty field
            $this->db()->getTableField() :
            // Existing column => check the metadata
            ($this->metadata()['fields'][$fieldName] ?? null);

        // Fill the data from the database with the data from the databag.
        return $field === null ? null : ColumnEntity::make($field, $column);
    }

    /**
     * @return array<ColumnEntity>
     */
    protected function getTableColumns(): array
    {
        // Fill the data from the database with the data from the databag or the form values.
        return array_map(fn(array $column) =>
            $this->getFieldColumn($column['name']), $this->columns());
    }
}

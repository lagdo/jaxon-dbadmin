<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Column;

use Lagdo\DbAdmin\Db\Page\Ddl\ColumnInputEntity;

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
        return $this->metadata ??= $this->db()->getTableMetadata($this->getTableName());
    }

    /**
     * @return array
     */
    protected function columns(): array
    {
        return $this->columns ??= $this->bag('dbadmin.table')->get('columns', []);
    }

    /**
     * @return ColumnInputEntity
     */
    protected function getEmptyColumn(): ColumnInputEntity
    {
        return new ColumnInputEntity($this->db()->getTableField());
    }

    /**
     * @param string $fieldName
     *
     * @return ColumnInputEntity|null
     */
    protected function getFieldColumn(string $fieldName): ColumnInputEntity|null
    {
        $column = $this->columns()[$fieldName] ?? null;
        if ($column === null) {
            return null;
        }

        $field = ColumnInputEntity::columnIsAdded($column) ?
            // Added column => empty field
            $this->db()->getTableField() :
            // Existing column => check the metadata
            ($this->metadata()['fields'][$fieldName] ?? null);

        // Fill the data from the database with the data from the databag.
        return $field === null ? null : ColumnInputEntity::newColumn($field, $column);
    }

    /**
     * @return array<ColumnInputEntity>
     */
    protected function getTableColumns(): array
    {
        // Fill the data from the database with the data from the databag or the form values.
        return array_map(fn(array $column) =>
            $this->getFieldColumn($column['name']), $this->columns());
    }
}

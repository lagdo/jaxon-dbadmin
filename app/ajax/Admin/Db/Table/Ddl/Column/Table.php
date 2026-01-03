<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Column;

use Jaxon\Attributes\Attribute\Databag;
use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Component;
use Lagdo\DbAdmin\Db\Page\Ddl\ColumnEntity;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function array_map;

/**
 * When creating or modifying a table, this component displays its columns.
 * It does not persist data. It only updates the UI.
 */
#[Databag('dbadmin.table')]
#[Exclude]
class Table extends Component
{
    /**
     * @var array
     */
    private $metadata;

    /**
     * @var array<ColumnEntity>
     */
    private $columns;

    /**
     * @var string
     */
    protected $formId = 'dbadmin-table-form';

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $columns = array_map(fn(ColumnEntity $column) => $column->toArray(), $this->columns);
        $this->bag('dbadmin.table')->set('columns', $columns);

        return $this->tableUi
            ->formId($this->formId)
            ->metadata($this->metadata)
            ->columns($this->columns)
            ->showColumns();
    }

    /**
     * @param ColumnEntity|null $column
     *
     * @return bool
     */
    private function columnIsInvalid(ColumnEntity|null $column): bool
    {
        // Null values and columns not found in the database are discarded.
        return $column === null ||
            ($column->status !== 'added' &&
            !isset($this->metadata['fields'][$column->name]));
    }

    /**
     * @param array $metadata
     * @param array<ColumnEntity> $columns
     *
     * @return void
     */
    public function show(array $metadata, array $columns = []): void
    {
        $this->metadata = $metadata;
        $this->columns = [];
        // Reset the columns positions and names.
        $position = 0;
        foreach ($columns as $column) {
            if ($this->columnIsInvalid($column)) {
                continue;
            }

            if ($column->status === 'added') {
                $column->name = "new_column_$position";
            }
            $column->position = $position++;
            $this->columns[$column->name] = $column;
        }

        $this->render();
    }

    /**
     * @param array $metadata
     *
     * @return void
     */
    public function load(array $metadata): void
    {
        $this->metadata = $metadata;
        $this->columns = array_map(fn(TableFieldEntity $field) =>
            new ColumnEntity($field), $metadata['fields']);

        $this->render();
    }
}

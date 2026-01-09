<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Column;

use Jaxon\Attributes\Attribute\Databag;
use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Component;
use Lagdo\DbAdmin\Db\UiData\Ddl\ColumnInputDto;
use Lagdo\DbAdmin\Driver\Dto\TableFieldDto;

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
     * @var array<ColumnInputDto>
     */
    private $columns;

    /**
     * @var string
     */
    protected $formId = 'dbadmin-table-form';

    /**
     * @param array $columns
     *
     * @return void
     */
    private function setColumns(array $columns): void
    {
        $this->columns = [];

        // Set the columns positions.
        $position = 0;
        foreach ($columns as $column) {
            $column->position = $position++;
            $this->columns["column_$position"] = $column;
        }

        // Save the columns in the databag.
        $this->bag('dbadmin.table')->set('columns',
            array_map(fn($column) => $column->toArray(), $this->columns));
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->tableUi
            ->formId($this->formId)
            ->metadata($this->metadata)
            ->columns($this->columns)
            ->showColumns();
    }

    /**
     * @param ColumnInputDto|null $column
     *
     * @return bool
     */
    private function columnIsValid(ColumnInputDto|null $column): bool
    {
        // Null values and columns not found in the database are discarded.
        return $column !== null && ($column->added() ||
            isset($this->metadata['fields'][$column->name]));
    }

    /**
     * @param array $metadata
     * @param array<ColumnInputDto> $columns
     *
     * @return void
     */
    public function show(array $metadata, array $columns = []): void
    {
        $this->metadata = $metadata;
        $this->setColumns(array_filter($columns,
            fn(ColumnInputDto|null $column) => $this->columnIsValid($column)));

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
        $this->setColumns(array_map(fn(TableFieldDto $field) =>
            new ColumnInputDto($field), $metadata['fields']));

        $this->render();
    }
}

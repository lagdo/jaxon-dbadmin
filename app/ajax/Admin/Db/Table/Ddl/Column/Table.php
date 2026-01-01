<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Column;

use Jaxon\Attributes\Attribute\Databag;
use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Component;
use Lagdo\DbAdmin\Db\Page\Ddl\ColumnEntity;

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
    private $tableData;

    /**
     * @var string
     */
    protected $formId = 'dbadmin-table-form';

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $columns = $this->stash()->get('table.columns');
        $callback = fn(ColumnEntity $column) => $column->toArray();
        $this->bag('dbadmin.table')->set('columns', array_map($callback, $columns));

        $metadata = $this->stash()->get('table.metadata');

        return $this->tableUi
            ->formId($this->formId)
            ->metadata($metadata)
            ->columns($columns)
            ->showColumns();
    }
}

<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Databag;
use Jaxon\Attributes\Attribute\Export;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\MainComponent;
use Lagdo\DbAdmin\Ajax\Admin\Page\PageActions;

use function Jaxon\je;

/**
 * Alter a table
 */
#[Databag('dbadmin.table')]
#[After('showBreadcrumbs')]
#[Export(['render'])]
class Alter extends MainComponent
{
    /**
     * The database table data.
     *
     * @var array|null
     */
    private $metadata = null;

    /**
     * @var string
     */
    protected $formId = 'dbadmin-table-data-form';

    /**
     * @return array
     */
    protected function metadata(): array
    {
        return $this->metadata ??= $this->db()->getTableMetadata($this->getTableName());
    }

    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        // Set main menu buttons
        $table = $this->getTableName();
        $values = je($this->formId)->rd()->form();
        $actions = [
            'table-save' => [
                'title' => $this->trans()->lang('Save'),
                'handler' => $this->rq(TableFunc::class)->alter($values)
                    ->confirm("Save changes on table $table?"),
            ],
            'table-changes' => [
                'title' => $this->trans()->lang('Changes'),
                'handler' => $this->rq(QueryFunc::class)->changes($values),
            ],
            'table-queries' => [
                'title' => $this->trans()->lang('Queries'),
                'handler' => $this->rq(QueryFunc::class)->alterTable($values),
            ],
            'table-back' => [
                'title' => $this->trans()->lang('Back'),
                'handler' => $this->rq(Table::class)->show($table),
            ],
        ];
        $this->cl(PageActions::class)->show($actions);
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $metadata = $this->metadata();
        $table = $metadata['table'];
        $editedTable = [
            'name' => $table->name,
            'engine' => $table->engine,
            'collation' => $table->collation,
            'comment' => $table->comment,
            'hasAutoIncrement' => $table->hasAutoIncrement,
            'autoIncrement' => $table->autoIncrement,
        ];

        return $this->tableUi
            ->table($editedTable)
            ->metadata($metadata)
            ->formId($this->formId)
            ->wrapper();
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $this->cl(Column\Table::class)->load($this->metadata());
    }
}

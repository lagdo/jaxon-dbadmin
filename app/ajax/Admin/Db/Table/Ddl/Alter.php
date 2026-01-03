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
    protected $formId = 'dbadmin-table-form';

    /**
     * @return array
     */
    protected function metadata(): array
    {
        return $this->metadata ??= $this->db()->getTableData($this->getTableName());
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
                'handler' => $this->rq(TableFunc::class)->alter($table, $values)
                    ->confirm("Save changes on table $table?"),
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
        $editedTable = [
            'name' => $metadata['table']->name,
            'engine' => $metadata['table']->engine,
            'collation' => $metadata['table']->collation,
            'comment' => $metadata['table']->comment,
        ];

        return $this->tableUi
            ->table($editedTable)
            ->support($metadata['support'])
            ->engines($metadata['engines'])
            ->collations($metadata['collations'])
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

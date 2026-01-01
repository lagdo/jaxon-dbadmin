<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Databag;
use Jaxon\Attributes\Attribute\Export;
use Lagdo\DbAdmin\Ajax\Admin\Db\Database\Tables;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\MainComponent;
use Lagdo\DbAdmin\Ajax\Admin\Page\PageActions;

use function Jaxon\je;
use function Jaxon\jq;

/**
 * Create a new table
 */
#[Databag('dbadmin.table')]
#[After('showBreadcrumbs')]
#[Export(['render'])]
class Create extends MainComponent
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
        return $this->metadata ??= $this->db()->getTableData();
    }

    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        $this->bag('dbadmin')->set('db.table.name', '');
        $this->bag('dbadmin.table')->set('columns', []);
        $this->stash()->set('table.columns', []);
        $this->stash()->set('table.metadata', $this->metadata());

        // Set main menu buttons
        $values = je($this->formId)->rd()->form();
        $length = jq(".{$this->formId}-column", "#{$this->formId}")->length;
        $actions = [
            'table-save' => [
                'title' => $this->trans()->lang('Save'),
                'handler' => $this->rq(TableFunc::class)->create($values)->ifgt($length, 0),
            ],
            'table-back' => [
                'title' => $this->trans()->lang('Back'),
                'handler' => $this->rq(Tables::class)->show(),
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

        return $this->tableUi
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
        $this->cl(Column\Table::class)->render();
    }
}

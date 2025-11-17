<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Ddl;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\App\Db\Database\Tables;
use Lagdo\DbAdmin\Ajax\App\Db\Table\MainComponent;
use Lagdo\DbAdmin\Ajax\App\Page\PageActions;

use function Jaxon\je;
use function Jaxon\jq;

/**
 * Create a new table
 */
#[Databag('dbadmin.table')]
class Create extends MainComponent
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
     * Default values for tables
     *
     * @var string[]
     */
    protected $defaults = ['autoIncrementCol' => '', 'engine' => '', 'collation' => ''];

    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        $this->bag('dbadmin')->set('db.table.name', '');
        $this->bag('dbadmin.table')->set('fields', []);
        $this->stash()->set('table.fields', []);

        $this->tableData = $this->db()->getTableData();

        // Set main menu buttons
        $values = je($this->formId)->rd()->form();
        $length = jq(".{$this->formId}-column", "#{$this->formId}")->length;
        $actions = [
            'table-save' => [
                'title' => $this->trans()->lang('Save'),
                'handler' => $this->rq()->save($values)->ifgt($length, 0),
            ],
            'table-cancel' => [
                'title' => $this->trans()->lang('Cancel'),
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
        return $this->tableUi
            ->support($this->tableData['support'])
            ->engines($this->tableData['engines'])
            ->collations($this->tableData['collations'])
            ->formId($this->formId)
            ->wrapper($this->rq(Columns::class));
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $this->cl(Columns::class)->render();
    }

    /**
     * Show the create table page
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function show(): void
    {
        $this->render();
    }

    /**
     * Create a new table
     *
     * @param array  $values      The table values
     *
     * @return void
     */
    #[Before('notYetAvailable')]
    public function save(array $values): void
    {
        // $fields = $this->bag('dbadmin.table')->get('fields');
        // $values = array_merge($this->defaults, $values);

        // $result = $this->db()->createTable($values);
        // if(!$result['success'])
        // {
        //     $this->alert()->error($result['error']);
        //     return;
        // }

        // $this->show($values['name']);
        // $this->alert()->success($result['message']);
    }
}

<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table\Ddl;

use Lagdo\DbAdmin\App\Ajax\Db\Table\Component;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

/**
 * Create a new table
 *
 * @databag dbadmin.table
 * @after showBreadcrumbs
 */
class Create extends Component
{
    /**
     * @var array
     */
    private $tableData;

    /**
     * @var string
     */
    protected $formId = 'adminer-table-form';

    /**
     * Default values for tables
     *
     * @var string[]
     */
    protected $defaults = ['autoIncrementCol' => '', 'engine' => '', 'collation' => ''];

    /**
     * @inheritDoc
     */
    protected function before()
    {
        $this->bag('dbadmin')->set('db.table.name', '');
        $this->bag('dbadmin.table')->set('fields', []);
        $this->stash()->set('table.fields', []);

        $this->tableData = $this->db->getTableData();
        // Make data available to views
        $this->view()->shareValues($this->tableData);

        // Set main menu buttons
        $this->cl(PageActions::class)->addTable($this->formId);
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui
            ->support($this->tableData['support'])
            ->engines($this->tableData['engines'])
            ->collations($this->tableData['collations'])
            ->tableWrapper($this->formId, $this->rq(Columns::class));
    }

    /**
     * @inheritDoc
     */
    protected function after()
    {
        $this->cl(Columns::class)->render();
    }

    /**
     * Create a new table
     *
     * @param array  $values      The table values
     *
     * @return void
     */
    public function save(array $values)
    {
        // $fields = $this->bag('dbadmin.table')->get('fields');
        // $values = array_merge($this->defaults, $values);

        // $result = $this->db->createTable($values);
        // if(!$result['success'])
        // {
        //     $this->alert()->error($result['error']);
        //     return;
        // }

        // $this->show($values['name']);
        // $this->alert()->success($result['message']);
    }
}

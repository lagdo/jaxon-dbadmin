<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table\Ddl;

use Lagdo\DbAdmin\App\Ajax\Db\Database\Tables;
use Lagdo\DbAdmin\App\Ajax\Db\Table\ContentComponent;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function Jaxon\jq;
use function Jaxon\pm;

/**
 * Create a new table
 *
 * @databag dbadmin.table
 * @after showBreadcrumbs
 */
class Create extends ContentComponent
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
        $contentId = 'adminer-database-content';
        $length = jq(".{$this->formId}-column", "#$contentId")->length;
        $values = pm()->form($this->formId);
        $actions = [
            'table-save' => [
                'title' => $this->trans->lang('Save'),
                'handler' => $this->rq()->save($values)->when($length),
            ],
            'table-cancel' => [
                'title' => $this->trans->lang('Cancel'),
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

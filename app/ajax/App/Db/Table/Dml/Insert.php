<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dml;

use Lagdo\DbAdmin\Ajax\App\Db\Table\ContentComponent;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Ddl\Table;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Select;
use Lagdo\DbAdmin\Ajax\App\Page\PageActions;

use function Jaxon\pm;

/**
 * This class provides insert and update query features on tables.
 */
class Insert extends ContentComponent
{
    /**
     * @var array
     */
    private $queryData;

    /**
     * The query form div id
     *
     * @var string
     */
    private $queryFormId = 'dbadmin-table-query-form';

    /**
     * @inheritDoc
     */
    protected function before()
    {
        // Make data available to views
        $this->view()->shareValues($this->queryData);

        // Set main menu buttons
        $table = $this->getTableName();
        $options = pm()->form($this->queryFormId);
        $actions = [
            'query-save' => [
                'title' => $this->trans()->lang('Save'),
                'handler' => $this->rq()->exec($options, true)
                    ->confirm($this->trans()->lang('Save this item?')),
            ],
            'query-save-select' => [
                'title' => $this->trans()->lang('Save and select'),
                'handler' => $this->rq()->exec($options, false)
                    ->confirm($this->trans()->lang('Save this item?')),
            ],
            'query-back' => [
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
        return $this->ui()->tableQueryForm($this->queryFormId, $this->queryData['fields']);
    }

    /**
     * Show the update query form
     *
     * @after showBreadcrumbs
     *
     * @return void
     */
    public function show()
    {
        $this->queryData = $this->db()->getQueryData($this->getTableName());
        // Show the error
        if(($this->queryData['error']))
        {
            $this->alert()->title($this->trans()->lang('Error'))->error($this->queryData['error']);
            return;
        }
        $this->render();
    }

    /**
     * Execute the insert query
     *
     * @after('call' => 'debugQueries')
     *
     * @param array  $options     The query options
     * @param bool $addNew        Add a new entry after saving the current one.
     *
     * @return void
     */
    public function exec(array $options, bool $addNew)
    {
        $table = $this->getTableName();
        $results = $this->db()->insertItem($table, $options);

        // Show the error
        if(($results['error']))
        {
            $this->alert()->title($this->trans()->lang('Error'))->error($results['error']);
            return;
        }
        $this->alert()->title($this->trans()->lang('Success'))->success($results['message']);

        $addNew ? $this->render() : $this->cl(Select::class)->show($table);
    }
}

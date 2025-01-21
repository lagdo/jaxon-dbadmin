<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table\Dml;

use Lagdo\DbAdmin\App\CallableDbClass;
use Lagdo\DbAdmin\App\Ajax\Page\Content;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function Jaxon\pm;

/**
 * This class provides insert and update query features on tables.
 */
class Table extends CallableDbClass
{
    /**
     * The query form div id
     *
     * @var string
     */
    private $queryFormId = 'adminer-table-query-form';

    /**
     * Show the insert query form
     *
     * @after showBreadcrumbs
     *
     * @return void
     */
    public function showInsert()
    {
        $table = $this->bag('dbadmin')->get('db.table.name');
        $queryData = $this->db->getQueryData($table);
        // Show the error
        if(($queryData['error']))
        {
            $this->alert()->title($this->lang('Error'))->error($queryData['error']);
            return;
        }
        // Make data available to views
        $this->view()->shareValues($queryData);

        // Set main menu buttons
        $this->cl(PageActions::class)->showQuery($table, $this->queryFormId, true);

        $content = $this->ui->tableQueryForm($this->queryFormId, $queryData['fields']);
        $this->cl(Content::class)->showHtml($content);
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
    public function execInsert(array $options, bool $addNew)
    {
        $table = $this->bag('dbadmin')->get('db.table.name');
        $results = $this->db->insertItem($table, $options);

        // Show the error
        if(($results['error']))
        {
            $this->alert()->title($this->lang('Error'))->error($results['error']);
            return;
        }
        $this->alert()->title($this->lang('Success'))->success($results['message']);

        // $addNew ? $this->showInsert() : $this->cl(Select::class)->show();
    }

    /**
     * Get back to the select query from which the update or delete was called
     *
     * @databag('name' => 'dbadmin.select')
     *
     * @return void
     */
    public function backToSelect()
    {
        // $select = $this->cl(Select::class);
        // $select->show(false);
        // $select->execSelect();
    }

    /**
     * Show the update query form
     *
     * @after showBreadcrumbs
     *
     * @param array  $rowIds        The row identifiers
     *
     * @return void
     */
    public function showUpdate(array $rowIds)
    {
        $table = $this->bag('dbadmin')->get('db.table.name');
        $queryData = $this->db->getQueryData($table, $rowIds, 'Edit item');
        // Show the error
        if(($queryData['error']))
        {
            $this->alert()->title($this->lang('Error'))->error($queryData['error']);
            return;
        }
        // Make data available to views
        $this->view()->shareValues($queryData);

        // Set main menu buttons
        $options = pm()->form($this->queryFormId);
        $actions = [
            [$this->trans->lang('Back'), $this->rq()->backToSelect(), true],
            [$this->trans->lang('Save'), $this->rq()->execUpdate($rowIds, $options)
                ->confirm($this->lang('Save this item?'))],
        ];
        $this->cl(PageActions::class)->refresh($actions);

        $content = $this->ui->tableQueryForm($this->queryFormId, $queryData['fields']);
        $this->cl(Content::class)->showHtml($content);
    }

    /**
     * Execute the update query
     *
     * @databag('name' => 'dbadmin.select')
     * @after('call' => 'debugQueries')
     *
     * @param array  $rowIds        The row selector
     * @param array  $options       The query options
     *
     * @return void
     */
    public function execUpdate(array $rowIds, array $options)
    {
        $options['where'] = $rowIds['where'];
        $options['null'] = $rowIds['null'];

        $table = $this->bag('dbadmin')->get('db.table.name');
        $results = $this->db->updateItem($table, $options);

        // Show the error
        if(($results['error']))
        {
            $this->alert()->title($this->lang('Error'))->error($results['error']);
            return;
        }
        $this->alert()->title($this->lang('Success'))->success($results['message']);
        $this->backToSelect();
    }

    /**
     * Execute the delete query
     *
     * @databag('name' => 'dbadmin.select')
     * @after('call' => 'debugQueries')
     *
     * @param array  $rowIds        The row identifiers
     *
     * @return void
     */
    public function execDelete(array $rowIds)
    {
        $table = $this->bag('dbadmin')->get('db.table.name');
        $results = $this->db->deleteItem($table, $rowIds);

        // Show the error
        if(($results['error']))
        {
            $this->alert()->title($this->lang('Error'))->error($results['error']);
            return;
        }
        $this->alert()->title($this->lang('Success'))->success($results['message']);
        $this->rq(Select::class)->execSelect();
    }
}

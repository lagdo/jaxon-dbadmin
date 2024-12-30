<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table\Dml;

use Jaxon\Response\Response;
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
     * @return Response
     */
    public function showInsert(): Response
    {
        $table = $this->bag('dbadmin')->get('db.table.name');
        $queryData = $this->db->getQueryData($table);
        // Show the error
        if(($queryData['error']))
        {
            $this->response->dialog->error($queryData['error'], $this->lang('Error'));
            return $this->response;
        }
        // Make data available to views
        $this->view()->shareValues($queryData);

        // Set main menu buttons
        $this->cl(PageActions::class)->showQuery($table, $this->queryFormId, true);

        $content = $this->ui->tableQueryForm($this->queryFormId, $queryData['fields']);
        $this->cl(Content::class)->showHtml($content);

        return $this->response;
    }

    /**
     * Execute the insert query
     *
     * @after('call' => 'debugQueries')
     *
     * @param array  $options     The query options
     * @param bool $addNew        Add a new entry after saving the current one.
     *
     * @return Response
     */
    public function execInsert(array $options, bool $addNew): Response
    {
        $table = $this->bag('dbadmin')->get('db.table.name');
        $results = $this->db->insertItem($table, $options);

        // Show the error
        if(($results['error']))
        {
            $this->response->dialog->error($results['error'], $this->lang('Error'));
            return $this->response;
        }
        $this->response->dialog->success($results['message'], $this->lang('Success'));

        // $addNew ? $this->showInsert() : $this->cl(Select::class)->show();

        return $this->response;
    }

    /**
     * Get back to the select query from which the update or delete was called
     *
     * @databag('name' => 'dbadmin.select')
     *
     * @return Response
     */
    public function backToSelect(): Response
    {
        // $select = $this->cl(Select::class);
        // $select->show(false);
        // $select->execSelect();

        return $this->response;
    }

    /**
     * Show the update query form
     *
     * @after showBreadcrumbs
     *
     * @param array  $rowIds        The row identifiers
     *
     * @return Response
     */
    public function showUpdate(array $rowIds): Response
    {
        $table = $this->bag('dbadmin')->get('db.table.name');
        $queryData = $this->db->getQueryData($table, $rowIds, 'Edit item');
        // Show the error
        if(($queryData['error']))
        {
            $this->response->dialog->error($queryData['error'], $this->lang('Error'));
            return $this->response;
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

        return $this->response;
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
     * @return Response
     */
    public function execUpdate(array $rowIds, array $options): Response
    {
        $options['where'] = $rowIds['where'];
        $options['null'] = $rowIds['null'];

        $table = $this->bag('dbadmin')->get('db.table.name');
        $results = $this->db->updateItem($table, $options);

        // Show the error
        if(($results['error']))
        {
            $this->response->dialog->error($results['error'], $this->lang('Error'));
            return $this->response;
        }
        $this->response->dialog->success($results['message'], $this->lang('Success'));
        $this->backToSelect();

        return $this->response;
    }

    /**
     * Execute the delete query
     *
     * @databag('name' => 'dbadmin.select')
     * @after('call' => 'debugQueries')
     *
     * @param array  $rowIds        The row identifiers
     *
     * @return Response
     */
    public function execDelete(array $rowIds): Response
    {
        $table = $this->bag('dbadmin')->get('db.table.name');
        $results = $this->db->deleteItem($table, $rowIds);

        // Show the error
        if(($results['error']))
        {
            $this->response->dialog->error($results['error'], $this->lang('Error'));
            return $this->response;
        }
        $this->response->dialog->success($results['message'], $this->lang('Success'));
        $this->rq(Select::class)->execSelect();

        return $this->response;
    }
}

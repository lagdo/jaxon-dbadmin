<?php

namespace Lagdo\DbAdmin\App\Ajax\Table;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Table;
use Lagdo\DbAdmin\App\CallableClass;

use Exception;

use function Jaxon\pm;

/**
 * This class provides insert and update query features on tables.
 *
 * @databag selection
 */
class Query extends CallableClass
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
     * @after('call' => 'showBreadcrumbs')
     *
     * @return Response
     */
    public function showInsert(): Response
    {
        [$server, $database, $schema, $table] = $this->bag('selection')->get('db');
        $queryData = $this->dbAdmin->getQueryData($server, $database, $schema, $table);
        // Show the error
        if(($queryData['error']))
        {
            $this->response->dialog->error($queryData['error'], $this->dbAdmin->lang('Error'));
            return $this->response;
        }
        // Make data available to views
        $this->view()->shareValues($queryData);

        // Set main menu buttons
        $content = isset($queryData['mainActions']) ?
            $this->uiBuilder->mainActions($queryData['mainActions']) : '';
        $this->response->html($this->package->getMainActionsId(), $content);

        $content = $this->uiBuilder->tableQueryForm($this->queryFormId, $queryData['fields']);
        $this->response->html($this->package->getDbContentId(), $content);

        $options = pm()->form($this->queryFormId);
        // Set onclick handlers on buttons
        $this->jq('#adminer-main-action-query-save')->click($this->rq()->execInsert($options, true)
            ->confirm($this->dbAdmin->lang('Save this item?')));
        $this->jq('#adminer-main-action-query-save-select')->click($this->rq()->execInsert($options, false)
            ->confirm($this->dbAdmin->lang('Save this item?')));
        $this->jq('#adminer-main-action-query-back')->click($this->rq(Table::class)->show($table));

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
        [$server, $database, $schema, $table] = $this->bag('selection')->get('db');
        $results = $this->dbAdmin->insertItem($server, $database, $schema, $table, $options);

        // Show the error
        if(($results['error']))
        {
            $this->response->dialog->error($results['error'], $this->dbAdmin->lang('Error'));
            return $this->response;
        }
        $this->response->dialog->success($results['message'], $this->dbAdmin->lang('Success'));

        $addNew ? $this->showInsert() : $this->cl(Select::class)->show($table);

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
        $select = $this->cl(Select::class);
        $select->show(false);
        $select->execSelect();

        return $this->response;
    }

    /**
     * Show the update query form
     *
     * @after('call' => 'showBreadcrumbs')
     *
     * @param array  $rowIds        The row identifiers
     *
     * @return Response
     */
    public function showUpdate(array $rowIds): Response
    {
        [$server, $database, $schema, $table] = $this->bag('selection')->get('db');
        $queryData = $this->dbAdmin->getQueryData($server, $database, $schema, $table, $rowIds, 'Edit item');
        // Show the error
        if(($queryData['error']))
        {
            $this->response->dialog->error($queryData['error'], $this->dbAdmin->lang('Error'));
            return $this->response;
        }
        // Make data available to views
        $this->view()->shareValues($queryData);

        // Set main menu buttons
        $content = isset($queryData['mainActions']) ?
            $this->uiBuilder->mainActions($queryData['mainActions']) : '';
        $this->response->html($this->package->getMainActionsId(), $content);

        $content = $this->uiBuilder->tableQueryForm($this->queryFormId, $queryData['fields']);
        $this->response->html($this->package->getDbContentId(), $content);

        $options = pm()->form($this->queryFormId);
        // Set onclick handlers on buttons
        $this->jq('#adminer-main-action-query-save')->click($this->rq()->execUpdate($rowIds, $options)
            ->confirm($this->dbAdmin->lang('Save this item?')));
        $this->jq('#adminer-main-action-query-back')->click($this->rq()->backToSelect());

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
        [$server, $database, $schema, $table] = $this->bag('selection')->get('db');
        $results = $this->dbAdmin->updateItem($server, $database, $schema, $table, $options);

        // Show the error
        if(($results['error']))
        {
            $this->response->dialog->error($results['error'], $this->dbAdmin->lang('Error'));
            return $this->response;
        }
        $this->response->dialog->success($results['message'], $this->dbAdmin->lang('Success'));
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
        [$server, $database, $schema, $table] = $this->bag('selection')->get('db');
        $results = $this->dbAdmin->deleteItem($server, $database, $schema, $table, $rowIds);

        // Show the error
        if(($results['error']))
        {
            $this->response->dialog->error($results['error'], $this->dbAdmin->lang('Error'));
            return $this->response;
        }
        $this->response->dialog->success($results['message'], $this->dbAdmin->lang('Success'));
        $this->rq(Select::class)->execSelect();

        return $this->response;
    }
}

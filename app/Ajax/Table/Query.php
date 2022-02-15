<?php

namespace Lagdo\DbAdmin\App\Ajax\Table;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Table;
use Lagdo\DbAdmin\App\CallableClass;

use Exception;

use function pm;

/**
 * This class provides insert and update query features on tables.
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
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The schema name
     * @param string $table       The table name
     *
     * @return Response
     */
    public function showInsert(string $server, string $database, string $schema, string $table): Response
    {
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
        $this->jq('#adminer-main-action-query-save')
            ->click($this->rq()->execInsert($server, $database, $schema, $table, $options, true)
            ->confirm($this->dbAdmin->lang('Save this item?')));
        $this->jq('#adminer-main-action-query-save-select')
            ->click($this->rq()->execInsert($server, $database, $schema, $table, $options, false)
            ->confirm($this->dbAdmin->lang('Save this item?')));
        $this->jq('#adminer-main-action-query-back')
            ->click($this->cl(Table::class)->rq()->show($server, $database, $schema, $table));

        return $this->response;
    }

    /**
     * Execute the insert query
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The schema name
     * @param string $table       The table name
     * @param array  $options     The query options
     * @param bool $addNew        Add a new entry after saving the current one.
     *
     * @return Response
     */
    public function execInsert(string $server, string $database, string $schema,
        string $table, array $options, bool $addNew): Response
    {
        $results = $this->dbAdmin->insertItem($server, $database, $schema, $table, $options);

        // Show the error
        if(($results['error']))
        {
            $this->response->dialog->error($results['error'], $this->dbAdmin->lang('Error'));
            return $this->response;
        }
        $this->response->dialog->success($results['message'], $this->dbAdmin->lang('Success'));

        $addNew ? $this->showInsert($server, $database, $schema, $table) :
            $this->cl(Select::class)->show($server, $database, $schema, $table);

        return $this->response;
    }

    /**
     * Get back to the select query from which the update or delete was called
     *
     * @param string $server        The database server
     * @param string $database      The database name
     * @param string $schema        The schema name
     * @param string $table         The table name
     *
     * @return Response
     */
    public function backToSelect(string $server, string $database, string $schema, string $table): Response
    {
        $select = $this->cl(Select::class);
        $select->show($server, $database, $schema, $table, false);
        $select->execSelect($server, $database, $schema, $table);

        return $this->response;
    }

    /**
     * Show the update query form
     *
     * @param string $server        The database server
     * @param string $database      The database name
     * @param string $schema        The schema name
     * @param string $table         The table name
     * @param array  $rowIds        The row identifiers
     *
     * @return Response
     */
    public function showUpdate(string $server, string $database, string $schema,
        string $table, array $rowIds): Response
    {
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
        $this->jq('#adminer-main-action-query-save')
            ->click($this->rq()->execUpdate($server, $database, $schema, $table, $rowIds, $options)
            ->confirm($this->dbAdmin->lang('Save this item?')));
        $this->jq('#adminer-main-action-query-back')
            ->click($this->rq()->backToSelect($server, $database, $schema, $table));

        return $this->response;
    }

    /**
     * Execute the update query
     *
     * @param string $server        The database server
     * @param string $database      The database name
     * @param string $schema        The schema name
     * @param string $table         The table name
     * @param array  $rowIds        The row selector
     * @param array  $options       The query options
     *
     * @return Response
     */
    public function execUpdate(string $server, string $database, string $schema,
        string $table, array $rowIds, array $options): Response
    {
        $options['where'] = $rowIds['where'];
        $options['null'] = $rowIds['null'];
        $results = $this->dbAdmin->updateItem($server, $database, $schema, $table, $options);

        // Show the error
        if(($results['error']))
        {
            $this->response->dialog->error($results['error'], $this->dbAdmin->lang('Error'));
            return $this->response;
        }
        $this->response->dialog->success($results['message'], $this->dbAdmin->lang('Success'));
        $this->backToSelect($server, $database, $schema, $table);

        return $this->response;
    }

    /**
     * Execute the delete query
     *
     * @param string $server        The database server
     * @param string $database      The database name
     * @param string $schema        The schema name
     * @param string $table         The table name
     * @param array  $rowIds        The row identifiers
     *
     * @return Response
     */
    public function execDelete(string $server, string $database, string $schema,
        string $table, array $rowIds): Response
    {
        $results = $this->dbAdmin->deleteItem($server, $database, $schema, $table, $rowIds);

        // Show the error
        if(($results['error']))
        {
            $this->response->dialog->error($results['error'], $this->dbAdmin->lang('Error'));
            return $this->response;
        }
        $this->response->dialog->success($results['message'], $this->dbAdmin->lang('Success'));
        $this->cl(Select::class)->rq()->execSelect($server, $database, $schema, $table);

        return $this->response;
    }
}

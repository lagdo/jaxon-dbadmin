<?php

namespace Lagdo\DbAdmin\App\Ajax\Table;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Table;
use Lagdo\DbAdmin\App\Ajax\Command;
use Lagdo\DbAdmin\App\CallableClass;

use Exception;

use function html_entity_decode;
use function pm;

/**
 * This class provides select query features on tables.
 */
class Select extends CallableClass
{
    /**
     * The select form div id
     *
     * @var string
     */
    private $selectFormId = 'adminer-table-select-form';

    /**
     * The columns form div id
     *
     * @var string
     */
    private $columnsFormId = 'adminer-table-select-columns-form';

    /**
     * The filters form div id
     *
     * @var string
     */
    private $filtersFormId = 'adminer-table-select-filters-form';

    /**
     * The sorting form div id
     *
     * @var string
     */
    private $sortingFormId = 'adminer-table-select-sorting-form';

    /**
     * The select query div id
     *
     * @var string
     */
    private $txtQueryId = 'adminer-table-select-query';

    /**
     * @param string $server
     * @param string $query
     *
     * @return void
     */
    private function showQuery(string $server, string $query)
    {
        $this->response->html($this->txtQueryId, '');
        // $this->response->script("jaxon.adminer.highlightSqlQuery('{$this->txtQueryId}', '$server', '$query')");
        $this->response->addCommand([
            'cmd' => 'dbadmin.hsql',
            'id' => $this->txtQueryId,
            'driver' => $server,
        ], html_entity_decode($query, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * Show the select query form
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The schema name
     * @param string $table       The table name
     *
     * @return Response
     */
    public function show(string $server, string $database, string $schema, string $table): Response
    {
        $selectData = $this->dbAdmin->getSelectData($server, $database, $schema, $table);
        // Make data available to views
        $this->view()->shareValues($selectData);

        // Set main menu buttons
        $content = isset($selectData['mainActions']) ?
            $this->uiBuilder->mainActions($selectData['mainActions']) : '';
        $this->response->html($this->package->getMainActionsId(), $content);

        $btnColumnsId = 'adminer-table-select-columns';
        $btnFiltersId = 'adminer-table-select-filters';
        $btnSortingId = 'adminer-table-select-sorting';
        $btnEditId = 'adminer-table-select-edit';
        $btnExecId = 'adminer-table-select-exec';
        $btnLimitId = 'adminer-table-select-limit';
        $btnLengthId = 'adminer-table-select-length';
        $ids = [
            'formId' => $this->selectFormId,
            'btnColumnsId' => $btnColumnsId,
            'btnFiltersId' => $btnFiltersId,
            'btnSortingId' => $btnSortingId,
            'btnEditId' => $btnEditId,
            'btnExecId' => $btnExecId,
            'btnLimitId' => $btnLimitId,
            'btnLengthId' => $btnLengthId,
            'txtQueryId' => $this->txtQueryId,
        ];
        $content = $this->uiBuilder->tableSelect($ids, $selectData['options']);
        $this->response->html($this->package->getDbContentId(), $content);
        // Show the query
        $this->showQuery($server, $selectData['query']);

        $options = pm()->form($this->selectFormId);
        // Set onclick handlers on buttons
        $this->jq('#adminer-main-action-select-back')
            ->click($this->cl(Table::class)->rq()->show($server, $database, $schema, $table));
        $this->jq("#$btnColumnsId")
            ->click($this->rq()->editColumns($server, $database, $schema, $table, $options));
        $this->jq("#$btnFiltersId")
            ->click($this->rq()->editFilters($server, $database, $schema, $table, $options));
        $this->jq("#$btnSortingId")
            ->click($this->rq()->editSorting($server, $database, $schema, $table, $options));
        $this->jq("#$btnLimitId")
            ->click($this->rq()->setQueryOptions($server, $database, $schema, $table, $options));
        $this->jq("#$btnLengthId")
            ->click($this->rq()->setQueryOptions($server, $database, $schema, $table, $options));
        $this->jq('#adminer-main-action-select-exec')
            ->click($this->rq()->execSelect($server, $database, $schema, $table, $options));
        $this->jq('#adminer-main-action-insert-table')
            ->click($this->cl(Query::class)->rq()->showInsert($server, $database, $schema, $table));
        $this->jq("#$btnExecId")
            ->click($this->rq()->execSelect($server, $database, $schema, $table, $options));
        $query = pm()->js('jaxon.adminer.editor.query');
        $this->jq("#$btnEditId")
            ->click($this->cl(Command::class)->rq()->showDatabaseForm($server, $database, $schema, $query));

        return $this->response;
    }

    /**
     * Execute the query
     *
     * @param string $server The database server
     * @param string $database The database name
     * @param string $schema The schema name
     * @param string $table The table name
     * @param array $options The query options
     * @param integer $page The page number
     *
     * @return Response
     * @throws Exception
     */
    public function execSelect(string $server, string $database, string $schema,
        string $table, array $options, int $page = 0): Response
    {
        if($page < 1)
        {
            $page = $this->bag('dbadmin.table')->get('select.page', 1);
        }
        $this->bag('dbadmin.table')->set('select.page', $page);

        $options['page'] = $page;
        $results = $this->dbAdmin->execSelect($server, $database, $schema, $table, $options);
        // Show the message
        $resultsId = 'adminer-table-select-results';
        if(($results['message']))
        {
            $this->response->html($resultsId, $results['message']);
            return $this->response;
        }
        // Make data available to views
        $this->view()->shareValues($results);

        // Set ids for row update/delete
        $rowIds = [];
        foreach($results['rows'] as $row)
        {
            $rowIds[] = $row["ids"];
        }
        // Note: don't use the var keyword when setting a variable,
        // because it will not make the variable globally accessible.
        $this->response->script("rowIds = JSON.parse('" . json_encode($rowIds) . "')");

        $btnEditRowClass = 'adminer-table-select-row-edit';
        $btnDeleteRowClass = 'adminer-table-select-row-delete';
        $content = $this->uiBuilder->selectResults($results['headers'], $results['rows'],
            $btnEditRowClass, $btnDeleteRowClass);
        $this->response->html($resultsId, $content);

        // The Jaxon ajax calls
        $updateCall = $this->cl(Query::class)->rq()->showUpdate($server, $database, $schema, $table,
            pm()->js("rowIds[rowId]"), $options);
        $deleteCall = $this->cl(Query::class)->rq()->execDelete($server, $database, $schema, $table,
            pm()->js("rowIds[rowId]"), $options)->confirm($this->dbAdmin->lang('Delete this item?'));

        // Wrap the ajax calls into functions
        $this->response->setFunction('updateRowItem', 'rowId', $updateCall);
        $this->response->setFunction('deleteRowItem', 'rowId', $deleteCall);

        // Set the functions as button event handlers
        $this->jq(".$btnEditRowClass", "#$resultsId")
            ->click(\rq()->func('updateRowItem', \jq()->attr('data-row-id')));
        $this->jq(".$btnDeleteRowClass", "#$resultsId")
            ->click(\rq()->func('deleteRowItem', \jq()->attr('data-row-id')));

        // Show the query
        $this->showQuery($server, $results['query']);

        // Pagination
        $paginator = $this->rq()->execSelect($server, $database, $schema, $table, $options, pm()->page())
            ->paginate($page, $results['limit'], $results['total']);
        $pagination = $this->uiBuilder->pagination($paginator->getPages());
        $this->response->html("adminer-table-select-pagination", $pagination);

        return $this->response;
    }

    /**
     * Change the query options
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The schema name
     * @param string $table       The table name
     * @param array  $options     The query options
     *
     * @return Response
     */
    public function setQueryOptions(string $server, string $database, string $schema,
        string $table, array $options): Response
    {
        $selectData = $this->dbAdmin->getSelectData($server, $database, $schema, $table, $options);
        // Display the new query
        $this->showQuery($server, $selectData['query']);

        return $this->response;
    }

    /**
     * Change the query columns
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The schema name
     * @param string $table       The table name
     * @param array  $options     The query options
     *
     * @return Response
     */
    public function editColumns(string $server, string $database, string $schema,
        string $table, array $options): Response
    {
        $selectData = $this->dbAdmin->getSelectData($server, $database, $schema, $table, $options);
        // Make data available to views
        // $this->view()->shareValues($selectData);

        // For handlers on buttons
        $targetId = $this->columnsFormId;
        $sourceId = "$targetId-item-template";
        $checkboxClass = "$targetId-item-checkbox";

        $title = 'Edit columns';
        $content = $this->uiBuilder->editQueryColumns($this->columnsFormId, $selectData['options']['columns'],
            "jaxon.adminer.insertSelectQueryItem('$targetId', '$sourceId')",
            "jaxon.adminer.removeSelectQueryItems('$targetId', '$checkboxClass')");
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->saveColumns($server, $database, $schema, $table,
                pm()->form($this->selectFormId), pm()->form($this->columnsFormId)),
        ]];
        $this->response->dialog->show($title, $content, $buttons);

        $count = \count($selectData['options']['columns']['values']);
        $this->response->script("jaxon.adminer.newItemIndex=$count");

        return $this->response;
    }

    /**
     * Change the query columns
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The schema name
     * @param string $table       The table name
     * @param array  $options     The current query options
     * @param array  $changed     The changed query options
     *
     * @return Response
     */
    public function saveColumns(string $server, string $database, string $schema,
        string $table, array $options, array $changed): Response
    {
        $options['columns'] = $changed['columns'] ?? [];
        $selectData = $this->dbAdmin->getSelectData($server, $database, $schema, $table, $options);

        // Hide the dialog
        $this->response->dialog->hide();

        // Display the new values
        $content = $this->uiBuilder->showQueryColumns($selectData['options']['columns']['values']);
        $this->response->html('adminer-table-select-columns-show', $content);
        // Display the new query
        $this->showQuery($server, $selectData['query']);

        return $this->response;
    }

    /**
     * Change the query filters
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The schema name
     * @param string $table       The table name
     * @param array  $options     The query options
     *
     * @return Response
     */
    public function editFilters(string $server, string $database, string $schema,
        string $table, array $options): Response
    {
        $selectData = $this->dbAdmin->getSelectData($server, $database, $schema, $table, $options);
        // Make data available to views
        // $this->view()->shareValues($selectData);

        // For handlers on buttons
        $targetId = $this->filtersFormId;
        $sourceId = "$targetId-item-template";
        $checkboxClass = "$targetId-item-checkbox";

        $title = 'Edit filters';
        $content = $this->uiBuilder->editQueryFilters($this->filtersFormId, $selectData['options']['filters'],
            "jaxon.adminer.insertSelectQueryItem('$targetId', '$sourceId')",
            "jaxon.adminer.removeSelectQueryItems('$targetId', '$checkboxClass')");
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->saveFilters($server, $database, $schema, $table,
                pm()->form($this->selectFormId), pm()->form($this->filtersFormId)),
        ]];
        $this->response->dialog->show($title, $content, $buttons);

        $count = \count($selectData['options']['filters']['values']);
        $this->response->script("jaxon.adminer.newItemIndex=$count");

        return $this->response;
    }

    /**
     * Change the query filters
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The schema name
     * @param string $table       The table name
     * @param array  $options     The current query options
     * @param array  $changed     The changed query options
     *
     * @return Response
     */
    public function saveFilters(string $server, string $database, string $schema,
        string $table, array $options, array $changed): Response
    {
        $options['where'] = $changed['where'] ?? [];
        $selectData = $this->dbAdmin->getSelectData($server, $database, $schema, $table, $options);

        // Hide the dialog
        $this->response->dialog->hide();

        // Display the new values
        $content = $this->uiBuilder->showQueryFilters($selectData['options']['filters']['values']);
        $this->response->html('adminer-table-select-filters-show', $content);
        // Display the new query
        $this->showQuery($server, $selectData['query']);

        return $this->response;
    }

    /**
     * Change the query sorting
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The schema name
     * @param string $table       The table name
     * @param array  $options     The query options
     *
     * @return Response
     */
    public function editSorting(string $server, string $database, string $schema,
        string $table, array $options): Response
    {
        $selectData = $this->dbAdmin->getSelectData($server, $database, $schema, $table, $options);
        // Make data available to views
        // $this->view()->shareValues($selectData);

        // For handlers on buttons
        $targetId = $this->sortingFormId;
        $sourceId = "$targetId-item-template";
        $checkboxClass = "$targetId-item-checkbox";

        $title = 'Edit order';
        $content = $this->uiBuilder->editQuerySorting($this->sortingFormId, $selectData['options']['sorting'],
            "jaxon.adminer.insertSelectQueryItem('$targetId', '$sourceId')",
            "jaxon.adminer.removeSelectQueryItems('$targetId', '$checkboxClass')");
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->saveSorting($server, $database, $schema, $table,
                pm()->form($this->selectFormId), pm()->form($this->sortingFormId)),
        ]];
        $this->response->dialog->show($title, $content, $buttons);

        $count = \count($selectData['options']['sorting']['values']);
        $this->response->script("jaxon.adminer.newItemIndex=$count");

        return $this->response;
    }

    /**
     * Change the query sorting
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The schema name
     * @param string $table       The table name
     * @param array  $options     The current query options
     * @param array  $changed     The changed query options
     *
     * @return Response
     */
    public function saveSorting(string $server, string $database, string $schema,
        string $table, array $options, array $changed): Response
    {
        $options['order'] = $changed['order'] ?? [];
        $options['desc'] = $changed['desc'] ?? [];
        $selectData = $this->dbAdmin->getSelectData($server, $database, $schema, $table, $options);

        // Hide the dialog
        $this->response->dialog->hide();

        // Display the new values
        $content = $this->uiBuilder->showQuerySorting($selectData['options']['sorting']['values']);
        $this->response->html('adminer-table-select-sorting-show', $content);
        // Display the new query
        $this->showQuery($server, $selectData['query']);

        return $this->response;
    }
}

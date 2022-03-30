<?php

namespace Lagdo\DbAdmin\App\Ajax\Table;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Table;
use Lagdo\DbAdmin\App\Ajax\Command;
use Lagdo\DbAdmin\App\CallableClass;

use Exception;

use function html_entity_decode;
use function jq;
use function rq;
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
    private $formOptionsId = 'adminer-table-select-options-form';

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
     * Default select options
     *
     * @var array
     */
    private $selectOptions = ['limit' => 50, 'text_length' => 100];

    /**
     * @param string $server
     * @param string $query
     *
     * @return void
     */
    private function showQuery(string $server, string $query)
    {
        $this->response->html($this->txtQueryId, '');
        // $this->response->script("jaxon.dbadmin.highlightSqlQuery('{$this->txtQueryId}', '$server', '$query')");
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
     * @param bool   $init
     *
     * @return Response
     * @throws Exception
     */
    public function show(string $server, string $database, string $schema, string $table, bool $init = true): Response
    {
        $selectData = $this->dbAdmin->getSelectData($server, $database, $schema, $table);
        // Make data available to views
        $this->view()->shareValues($selectData);

        // Initialize select options
        if ($init) {
            $this->bag('dbadmin.select')->set('options', $this->selectOptions);
        }

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
            'formId' => $this->formOptionsId,
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
        if ($init) {
            $this->showQuery($server, $selectData['query']);
        }

        // Set onclick handlers on buttons
        $this->jq('#adminer-main-action-select-back')
            ->click($this->cl(Table::class)->rq()->show($server, $database, $schema, $table));
        $this->jq("#$btnColumnsId")
            ->click($this->rq()->editColumns($server, $database, $schema, $table));
        $this->jq("#$btnFiltersId")
            ->click($this->rq()->editFilters($server, $database, $schema, $table));
        $this->jq("#$btnSortingId")
            ->click($this->rq()->editSorting($server, $database, $schema, $table));
        $this->jq('#adminer-main-action-select-exec')
            ->click($this->rq()->execSelect($server, $database, $schema, $table));
        $this->jq('#adminer-main-action-insert-table')
            ->click($this->cl(Query::class)->rq()->showInsert($server, $database, $schema, $table));
        $this->jq("#$btnExecId")
            ->click($this->rq()->execSelect($server, $database, $schema, $table));
        $query = pm()->js('jaxon.dbadmin.editor.query');
        $this->jq("#$btnEditId")
            ->click($this->cl(Command::class)->rq()->showDatabaseForm($server, $database, $schema, $query));
        // Select options form
        $options = pm()->form($this->formOptionsId);
        $this->jq("#$btnLimitId")
            ->click($this->rq()->setQueryOptions($server, $database, $schema, $table, $options));
        $this->jq("#$btnLengthId")
            ->click($this->rq()->setQueryOptions($server, $database, $schema, $table, $options));

        return $this->response;
    }

    /**
     * Execute the query
     *
     * @param string $server The database server
     * @param string $database The database name
     * @param string $schema The schema name
     * @param string $table The table name
     * @param integer $page The page number
     *
     * @return Response
     * @throws Exception
     */
    public function execSelect(string $server, string $database, string $schema, string $table, int $page = 0): Response
    {
        // Select options
        $options = $this->bag('dbadmin.select')->get('options', $this->selectOptions);
        if($page < 1)
        {
            $page = $this->bag('dbadmin.select')->get('exec.page', 1);
        }
        $this->bag('dbadmin.select')->set('exec.page', $page);

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
        $this->response->script("jaxon.dbadmin.rowIds = JSON.parse('" . json_encode($rowIds) . "')");

        $btnEditRowClass = 'adminer-table-select-row-edit';
        $btnDeleteRowClass = 'adminer-table-select-row-delete';
        $content = $this->uiBuilder->selectResults($results['headers'], $results['rows'],
            $btnEditRowClass, $btnDeleteRowClass);
        $this->response->html($resultsId, $content);

        // The Jaxon ajax calls
        $updateCall = $this->cl(Query::class)->rq()->showUpdate($server, $database, $schema, $table,
            pm()->js("jaxon.dbadmin.rowIds[rowId]"));
        $deleteCall = $this->cl(Query::class)->rq()->execDelete($server, $database, $schema, $table,
            pm()->js("jaxon.dbadmin.rowIds[rowId]"))->confirm($this->dbAdmin->lang('Delete this item?'));

        // Wrap the ajax calls into functions
        $this->response->setFunction('updateRowItem', 'rowId', $updateCall);
        $this->response->setFunction('deleteRowItem', 'rowId', $deleteCall);

        // Set the functions as button event handlers
        $this->jq(".$btnEditRowClass", "#$resultsId")
            ->click(rq()->func('updateRowItem', jq()->attr('data-row-id')));
        $this->jq(".$btnDeleteRowClass", "#$resultsId")
            ->click(rq()->func('deleteRowItem', jq()->attr('data-row-id')));

        // Show the query
        $this->showQuery($server, $results['query']);

        // Pagination
        $pages = $this->rq()->execSelect($server, $database, $schema, $table, pm()->page())
            ->pages($page, $results['limit'], $results['total']);
        $pagination = $this->uiBuilder->pagination($pages);
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
     * @param array  $formValues  The form values
     *
     * @return Response
     */
    public function setQueryOptions(string $server, string $database, string $schema,
        string $table, array $formValues): Response
    {
        // Select options
        $options = $this->bag('dbadmin.select')->get('options', $this->selectOptions);
        $options['limit'] = $formValues['limit'] ?? 50;
        $options['text_length'] = $formValues['text_length'] ?? 100;
        $this->bag('dbadmin.select')->set('options', $options);

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
     *
     * @return Response
     */
    public function editColumns(string $server, string $database, string $schema, string $table): Response
    {
        // Select options
        $options = $this->bag('dbadmin.select')->get('options', $this->selectOptions);

        $selectData = $this->dbAdmin->getSelectData($server, $database, $schema, $table, $options);
        // Make data available to views
        // $this->view()->shareValues($selectData);

        // For handlers on buttons
        $targetId = $this->columnsFormId;
        $sourceId = "$targetId-item-template";
        $checkboxClass = "$targetId-item-checkbox";

        $title = 'Edit columns';
        $content = $this->uiBuilder->editQueryColumns($this->columnsFormId, $selectData['options']['columns'],
            "jaxon.dbadmin.insertSelectQueryItem('$targetId', '$sourceId')",
            "jaxon.dbadmin.removeSelectQueryItems('$targetId', '$checkboxClass')");
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->saveColumns($server, $database, $schema, $table, pm()->form($this->columnsFormId)),
        ]];
        $this->response->dialog->show($title, $content, $buttons);

        $count = \count($selectData['options']['columns']['values']);
        $this->response->script("jaxon.dbadmin.newItemIndex=$count");

        return $this->response;
    }

    /**
     * Change the query columns
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The schema name
     * @param string $table       The table name
     * @param array  $formValues  The form values
     *
     * @return Response
     */
    public function saveColumns(string $server, string $database, string $schema,
        string $table, array $formValues): Response
    {
        // Select options
        $options = $this->bag('dbadmin.select')->get('options', $this->selectOptions);
        $options['columns'] = $formValues['columns'] ?? [];
        $this->bag('dbadmin.select')->set('options', $options);

        $selectData = $this->dbAdmin->getSelectData($server, $database, $schema, $table, $options);
        // Hide the dialog
        $this->response->dialog->hide();
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
     *
     * @return Response
     */
    public function editFilters(string $server, string $database, string $schema, string $table): Response
    {
        // Select options
        $options = $this->bag('dbadmin.select')->get('options', $this->selectOptions);

        $selectData = $this->dbAdmin->getSelectData($server, $database, $schema, $table, $options);
        // Make data available to views
        // $this->view()->shareValues($selectData);

        // For handlers on buttons
        $targetId = $this->filtersFormId;
        $sourceId = "$targetId-item-template";
        $checkboxClass = "$targetId-item-checkbox";

        $title = 'Edit filters';
        $content = $this->uiBuilder->editQueryFilters($this->filtersFormId, $selectData['options']['filters'],
            "jaxon.dbadmin.insertSelectQueryItem('$targetId', '$sourceId')",
            "jaxon.dbadmin.removeSelectQueryItems('$targetId', '$checkboxClass')");
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->saveFilters($server, $database, $schema, $table, pm()->form($this->filtersFormId)),
        ]];
        $this->response->dialog->show($title, $content, $buttons);

        $count = \count($selectData['options']['filters']['values']);
        $this->response->script("jaxon.dbadmin.newItemIndex=$count");

        return $this->response;
    }

    /**
     * Change the query filters
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The schema name
     * @param string $table       The table name
     * @param array  $formValues  The form values
     *
     * @return Response
     */
    public function saveFilters(string $server, string $database, string $schema,
        string $table, array $formValues): Response
    {
        // Select options
        $options = $this->bag('dbadmin.select')->get('options', $this->selectOptions);
        $options['where'] = $formValues['where'] ?? [];
        $this->bag('dbadmin.select')->set('options', $options);

        $selectData = $this->dbAdmin->getSelectData($server, $database, $schema, $table, $options);
        // Hide the dialog
        $this->response->dialog->hide();
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
     *
     * @return Response
     */
    public function editSorting(string $server, string $database, string $schema, string $table): Response
    {
        // Select options
        $options = $this->bag('dbadmin.select')->get('options', $this->selectOptions);

        $selectData = $this->dbAdmin->getSelectData($server, $database, $schema, $table, $options);
        // Make data available to views
        // $this->view()->shareValues($selectData);

        // For handlers on buttons
        $targetId = $this->sortingFormId;
        $sourceId = "$targetId-item-template";
        $checkboxClass = "$targetId-item-checkbox";

        $title = 'Edit order';
        $content = $this->uiBuilder->editQuerySorting($this->sortingFormId, $selectData['options']['sorting'],
            "jaxon.dbadmin.insertSelectQueryItem('$targetId', '$sourceId')",
            "jaxon.dbadmin.removeSelectQueryItems('$targetId', '$checkboxClass')");
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->saveSorting($server, $database, $schema, $table, pm()->form($this->sortingFormId)),
        ]];
        $this->response->dialog->show($title, $content, $buttons);

        $count = \count($selectData['options']['sorting']['values']);
        $this->response->script("jaxon.dbadmin.newItemIndex=$count");

        return $this->response;
    }

    /**
     * Change the query sorting
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The schema name
     * @param string $table       The table name
     * @param array  $formValues  The form values
     *
     * @return Response
     */
    public function saveSorting(string $server, string $database, string $schema,
        string $table, array $formValues): Response
    {
        // Select options
        $options = $this->bag('dbadmin.select')->get('options', $this->selectOptions);
        $options['order'] = $formValues['order'] ?? [];
        $options['desc'] = $formValues['desc'] ?? [];
        $this->bag('dbadmin.select')->set('options', $options);

        $selectData = $this->dbAdmin->getSelectData($server, $database, $schema, $table, $options);
        // Hide the dialog
        $this->response->dialog->hide();
        // Display the new query
        $this->showQuery($server, $selectData['query']);

        return $this->response;
    }
}

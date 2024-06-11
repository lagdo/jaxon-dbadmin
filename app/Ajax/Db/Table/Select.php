<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Db\Table;
use Lagdo\DbAdmin\App\Ajax\Db\Command;
use Lagdo\DbAdmin\App\CallableDbClass;

use Exception;

use function count;
use function html_entity_decode;
use function Jaxon\jq;
use function Jaxon\rq;
use function Jaxon\pm;

/**
 * This class provides select query features on tables.
 */
class Select extends CallableDbClass
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
     * @param string $query
     *
     * @return void
     */
    private function showQuery(string $query)
    {
        [$server, ] = $this->bag('dbadmin')->get('db');
        $this->response->html($this->txtQueryId, '');
        // $this->response->call("jaxon.dbadmin.highlightSqlQuery", $this->txtQueryId, $server, $query);
        $this->response->addCommand([
            'cmd' => 'dbadmin.hsqlquery',
            'id' => $this->txtQueryId,
            'server' => $server,
        ], html_entity_decode($query, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * Show the select query form
     *
     * @after('call' => 'showBreadcrumbs')
     *
     * @param bool   $init
     *
     * @return Response
     * @throws Exception
     */
    public function show(bool $init = true): Response
    {
        $table = $this->bag('dbadmin')->get('db.table');
        $selectData = $this->db->getSelectData($table);
        // Make data available to views
        $this->view()->shareValues($selectData);

        // Initialize select options
        if ($init) {
            $this->bag('dbadmin')->set('options', $this->selectOptions);
        }

        // Set main menu buttons
        $content = isset($selectData['mainActions']) ?
            $this->ui->mainActions($selectData['mainActions']) : '';
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
        $content = $this->ui->tableSelect($ids, $selectData['options']);
        $this->response->html($this->package->getDbContentId(), $content);

        // Show the query
        if ($init) {
            $this->showQuery($selectData['query']);
        }

        // Set onclick handlers on buttons
        $this->jq('#adminer-main-action-select-back')->click($this->rq(Table::class)->show($table));
        $this->jq("#$btnColumnsId")->click($this->rq()->editColumns());
        $this->jq("#$btnFiltersId")->click($this->rq()->editFilters());
        $this->jq("#$btnSortingId")->click($this->rq()->editSorting());
        $this->jq('#adminer-main-action-select-exec')->click($this->rq()->execSelect());
        $this->jq('#adminer-main-action-insert-table')->click($this->rq(Query::class)->showInsert());
        $this->jq("#$btnExecId")->click($this->rq()->execSelect());
        $query = pm()->js('jaxon.dbadmin.editor.query');
        $this->jq("#$btnEditId")->click($this->rq(Command::class)->showDatabaseForm($query));
        // Select options form
        $options = pm()->form($this->formOptionsId);
        $this->jq("#$btnLimitId")->click($this->rq()->setQueryOptions($options));
        $this->jq("#$btnLengthId")->click($this->rq()->setQueryOptions($options));

        return $this->response;
    }

    /**
     * Execute the query
     *
     * @after('call' => 'debugQueries')
     *
     * @param integer $page The page number
     *
     * @return Response
     * @throws Exception
     */
    public function execSelect(int $page = 0): Response
    {
        // Select options
        $options = $this->bag('dbadmin')->get('options', $this->selectOptions);
        if($page < 1)
        {
            $page = $this->bag('dbadmin')->get('exec.page', 1);
        }
        $this->bag('dbadmin')->set('exec.page', $page);

        $options['page'] = $page;
        $table = $this->bag('dbadmin')->get('db.table');
        $results = $this->db->execSelect($table, $options);
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
        // $this->response->script("jaxon.dbadmin.rowIds = JSON.parse('" . json_encode($rowIds) . "')");
        $this->response->addCommand(['cmd' => 'dbadmin.row.ids.set'], $rowIds);

        $btnEditRowClass = 'adminer-table-select-row-edit';
        $btnDeleteRowClass = 'adminer-table-select-row-delete';
        $content = $this->ui->selectResults($results['headers'], $results['rows'],
            $btnEditRowClass, $btnDeleteRowClass);
        $this->response->html($resultsId, $content);

        // The Jaxon ajax calls
        $updateCall = $this->rq(Query::class)->showUpdate(pm()->js("jaxon.dbadmin.rowIds[rowId]"));
        $deleteCall = $this->rq(Query::class)->execDelete(pm()->js("jaxon.dbadmin.rowIds[rowId]"))
            ->confirm($this->lang('Delete this item?'));

        // Wrap the ajax calls into functions
        $this->response->setFunction('updateRowItem', 'rowId', $updateCall);
        $this->response->setFunction('deleteRowItem', 'rowId', $deleteCall);

        // Set the functions as button event handlers
        $this->jq(".$btnEditRowClass", "#$resultsId")->click(rq('.')->updateRowItem(jq()->attr('data-row-id')));
        $this->jq(".$btnDeleteRowClass", "#$resultsId")->click(rq('.')->deleteRowItem(jq()->attr('data-row-id')));

        // Show the query
        $this->showQuery($results['query']);

        // Pagination
        $pages = $this->rq()->execSelect(pm()->page())->pages($page, $results['limit'], $results['total']);
        $pagination = $this->ui->pagination($pages);
        $this->response->html("adminer-table-select-pagination", $pagination);

        return $this->response;
    }

    /**
     * Change the query options
     *
     * @param array  $formValues  The form values
     *
     * @return Response
     */
    public function setQueryOptions(array $formValues): Response
    {
        // Select options
        $options = $this->bag('dbadmin')->get('options', $this->selectOptions);
        $options['limit'] = $formValues['limit'] ?? 50;
        $options['text_length'] = $formValues['text_length'] ?? 100;
        $this->bag('dbadmin')->set('options', $options);

        $table = $this->bag('dbadmin')->get('db.table');
        $selectData = $this->db->getSelectData($table, $options);
        // Display the new query
        $this->showQuery($selectData['query']);

        return $this->response;
    }

    /**
     * Change the query columns
     *
     * @return Response
     */
    public function editColumns(): Response
    {
        // Select options
        $table = $this->bag('dbadmin')->get('db.table');
        $options = $this->bag('dbadmin')->get('options', $this->selectOptions);
        $selectData = $this->db->getSelectData($table, $options);
        // Make data available to views
        // $this->view()->shareValues($selectData);

        // For handlers on buttons
        $targetId = $this->columnsFormId;
        $sourceId = "$targetId-item-template";
        $checkboxClass = "$targetId-item-checkbox";

        $title = 'Edit columns';
        $content = $this->ui->editQueryColumns($this->columnsFormId,
            $selectData['options']['columns'],
            "jaxon.dbadmin.insertSelectQueryItem('$targetId', '$sourceId')",
            "jaxon.dbadmin.removeSelectQueryItems('$targetId', '$checkboxClass')");
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->saveColumns(pm()->form($this->columnsFormId)),
        ]];
        $this->response->dialog->show($title, $content, $buttons);

        $count = count($selectData['options']['columns']['values']);
        // $this->response->script("jaxon.dbadmin.newItemIndex=$count");
        $this->response->addCommand(['cmd' => 'dbadmin.new.index.set'], $count);

        return $this->response;
    }

    /**
     * Change the query columns
     *
     * @param array  $formValues  The form values
     *
     * @return Response
     */
    public function saveColumns(array $formValues): Response
    {
        // Select options
        $options = $this->bag('dbadmin')->get('options', $this->selectOptions);
        $options['columns'] = $formValues['columns'] ?? [];
        $this->bag('dbadmin')->set('options', $options);

        $table = $this->bag('dbadmin')->get('db.table');
        $selectData = $this->db->getSelectData($table, $options);
        // Hide the dialog
        $this->response->dialog->hide();
        // Display the new query
        $this->showQuery($selectData['query']);

        return $this->response;
    }

    /**
     * Change the query filters
     *
     * @return Response
     */
    public function editFilters(): Response
    {
        // Select options
        $table = $this->bag('dbadmin')->get('db.table');
        $options = $this->bag('dbadmin')->get('options', $this->selectOptions);
        $selectData = $this->db->getSelectData($table, $options);
        // Make data available to views
        // $this->view()->shareValues($selectData);

        // For handlers on buttons
        $targetId = $this->filtersFormId;
        $sourceId = "$targetId-item-template";
        $checkboxClass = "$targetId-item-checkbox";

        $title = 'Edit filters';
        $content = $this->ui->editQueryFilters($this->filtersFormId,
            $selectData['options']['filters'],
            "jaxon.dbadmin.insertSelectQueryItem('$targetId', '$sourceId')",
            "jaxon.dbadmin.removeSelectQueryItems('$targetId', '$checkboxClass')");
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->saveFilters(pm()->form($this->filtersFormId)),
        ]];
        $this->response->dialog->show($title, $content, $buttons);

        $count = count($selectData['options']['filters']['values']);
        // $this->response->script("jaxon.dbadmin.newItemIndex=$count");
        $this->response->addCommand(['cmd' => 'dbadmin.new.index.set'], $count);

        return $this->response;
    }

    /**
     * Change the query filters
     *
     * @param array  $formValues  The form values
     *
     * @return Response
     */
    public function saveFilters(array $formValues): Response
    {
        // Select options
        $options = $this->bag('dbadmin')->get('options', $this->selectOptions);
        $options['where'] = $formValues['where'] ?? [];
        $this->bag('dbadmin')->set('options', $options);

        $table = $this->bag('dbadmin')->get('db.table');
        $selectData = $this->db->getSelectData($table, $options);
        // Hide the dialog
        $this->response->dialog->hide();
        // Display the new query
        $this->showQuery($selectData['query']);

        return $this->response;
    }

    /**
     * Change the query sorting
     *
     * @return Response
     */
    public function editSorting(): Response
    {
        // Select options
        $table = $this->bag('dbadmin')->get('db.table');
        $options = $this->bag('dbadmin')->get('options', $this->selectOptions);
        $selectData = $this->db->getSelectData($table, $options);
        // Make data available to views
        // $this->view()->shareValues($selectData);

        // For handlers on buttons
        $targetId = $this->sortingFormId;
        $sourceId = "$targetId-item-template";
        $checkboxClass = "$targetId-item-checkbox";

        $title = 'Edit order';
        $content = $this->ui->editQuerySorting($this->sortingFormId,
            $selectData['options']['sorting'],
            "jaxon.dbadmin.insertSelectQueryItem('$targetId', '$sourceId')",
            "jaxon.dbadmin.removeSelectQueryItems('$targetId', '$checkboxClass')");
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->saveSorting(pm()->form($this->sortingFormId)),
        ]];
        $this->response->dialog->show($title, $content, $buttons);

        $count = count($selectData['options']['sorting']['values']);
        // $this->response->script("jaxon.dbadmin.newItemIndex=$count");
        $this->response->addCommand(['cmd' => 'dbadmin.new.index.set'], $count);

        return $this->response;
    }

    /**
     * Change the query sorting
     *
     * @param array  $formValues  The form values
     *
     * @return Response
     */
    public function saveSorting(array $formValues): Response
    {
        // Select options
        $options = $this->bag('dbadmin')->get('options', $this->selectOptions);
        $options['order'] = $formValues['order'] ?? [];
        $options['desc'] = $formValues['desc'] ?? [];
        $this->bag('dbadmin')->set('options', $options);

        $table = $this->bag('dbadmin')->get('db.table');
        $selectData = $this->db->getSelectData($table, $options);
        // Hide the dialog
        $this->response->dialog->hide();
        // Display the new query
        $this->showQuery($selectData['query']);

        return $this->response;
    }
}

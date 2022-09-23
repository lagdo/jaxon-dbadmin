<?php

namespace Lagdo\DbAdmin\App\Ajax;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Table\Column;
use Lagdo\DbAdmin\App\Ajax\Table\Select;
use Lagdo\DbAdmin\App\Ajax\Table\Query;
use Lagdo\DbAdmin\App\CallableClass;

use Exception;

use function array_merge;
use function Jaxon\jq;
use function Jaxon\pm;

class Table extends CallableClass
{
    /**
     * The form id
     * @var string
     */
    protected $formId = 'adminer-table-form';

    /**
     * The table id
     * @var string
     */
    protected $tableId = 'adminer-table-header';

    /**
     * Default values for tables
     * @var string[]
     */
    protected $defaults = ['autoIncrementCol' => '', 'engine' => '', 'collation' => ''];

    /**
     * Display the content of a tab
     *
     * @param array  $tableData The data to be displayed in the view
     * @param string $tabId     The tab container id
     *
     * @return void
     */
    protected function showTab(array $tableData, string $tabId)
    {
        // Make data available to views
        $this->view()->shareValues($tableData);

        $content = $this->uiBuilder->mainContent($this->renderMainContent());
        $this->response->html($tabId, $content);
    }

    /**
     * Show detailed info of a given table
     *
     * @after('call' => 'showBreadcrumbs')
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The database schema
     * @param string $table       The table name
     *
     * @return Response
     */
    public function show(string $server, string $database, string $schema, string $table): Response
    {
        $tableInfo = $this->dbAdmin->getTableInfo($server, $database, $schema, $table);
        // Make table info available to views
        $this->view()->shareValues($tableInfo);

        // Test the data bag

        // Set main menu buttons
        $content = isset($tableInfo['mainActions']) ?
            $this->uiBuilder->mainActions($tableInfo['mainActions']) : '';
        $this->response->html($this->package->getMainActionsId(), $content);

        $content = $this->uiBuilder->mainDbTable($tableInfo['tabs']);
        $this->response->html($this->package->getDbContentId(), $content);

        // Show fields
        $fieldsInfo = $this->dbAdmin->getTableFields($server, $database, $schema, $table);
        $this->showTab($fieldsInfo, 'tab-content-fields');

        // Show indexes
        $indexesInfo = $this->dbAdmin->getTableIndexes($server, $database, $schema, $table);
        if(\is_array($indexesInfo))
        {
            $this->showTab($indexesInfo, 'tab-content-indexes');
        }

        // Show foreign keys
        $foreignKeysInfo = $this->dbAdmin->getTableForeignKeys($server, $database, $schema, $table);
        if(\is_array($foreignKeysInfo))
        {
            $this->showTab($foreignKeysInfo, 'tab-content-foreign-keys');
        }

        // Show triggers
        $triggersInfo = $this->dbAdmin->getTableTriggers($server, $database, $schema, $table);
        if(\is_array($triggersInfo))
        {
            $this->showTab($triggersInfo, 'tab-content-triggers');
        }

        // Set onclick handlers on toolbar buttons
        $this->jq('#adminer-main-action-edit-table')
            ->click($this->rq()->edit($server, $database, $schema, $table));
        $this->jq('#adminer-main-action-drop-table')
            ->click($this->rq()->drop($server, $database, $schema, $table)
            ->confirm("Drop table $table?"));
        $this->jq('#adminer-main-action-select-table')
            ->click($this->cl(Select::class)->rq()->show($server, $database, $schema, $table));
        $this->jq('#adminer-main-action-insert-table')
            ->click($this->cl(Query::class)->rq()->showInsert($server, $database, $schema, $table));

        return $this->response;
    }

    /**
     * Create a new table
     *
     * @after('call' => 'showBreadcrumbs')
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The database schema
     *
     * @return Response
     */
    public function add(string $server, string $database, string $schema): Response
    {
        $tableData = $this->dbAdmin->getTableData($server, $database, $schema);
        // Make data available to views
        $this->view()->shareValues($tableData);

        // Set main menu buttons
        $content = isset($tableData['mainActions']) ?
            $this->uiBuilder->mainActions($tableData['mainActions']) : '';
        $this->response->html($this->package->getMainActionsId(), $content);

        $contentId = $this->package->getDbContentId();
        $content = $this->uiBuilder->tableForm($this->formId, $tableData['support'],
            $tableData['engines'], $tableData['collations']);
        $this->response->html($contentId, $content);

        // Set onclick handlers on toolbar buttons
        $length = jq(".{$this->formId}-column", "#$contentId")->length;
        $values = pm()->form($this->formId);
        $this->jq('#adminer-main-action-table-save')
            ->click($this->rq()->create($server, $database, $schema, $values)
            ->when($length));
        $this->jq('#adminer-main-action-table-cancel')
            ->click($this->cl(Database::class)->rq()->showTables($server, $database, $schema));
        $this->jq('#adminer-table-column-add')
            ->click($this->cl(Column::class)->rq()->add($server, $database, $schema, $length));

        return $this->response;
    }

    /**
     * Update a given table
     *
     * @after('call' => 'showBreadcrumbs')
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The database schema
     * @param string $table       The table name
     *
     * @return Response
     */
    public function edit(string $server, string $database, string $schema, string $table): Response
    {
        $tableData = $this->dbAdmin->getTableData($server, $database, $schema, $table);
        // Make data available to views
        $this->view()->shareValues($tableData);

        // Set main menu buttons
        $content = isset($tableData['mainActions']) ?
            $this->uiBuilder->mainActions($tableData['mainActions']) : '';
        $this->response->html($this->package->getMainActionsId(), $content);

        $contentId = $this->package->getDbContentId();
        $editedTable = [
            'name' => $tableData['table']->name,
            'engine' => $tableData['table']->engine,
            'collation' => $tableData['table']->collation,
            'comment' => $tableData['table']->comment,
        ];
        $content = $this->uiBuilder->tableForm($this->formId, $tableData['support'], $tableData['engines'],
            $tableData['collations'], $tableData['unsigned'] ?? [], $tableData['foreignKeys'],
            $tableData['options'], $editedTable, $tableData['fields']);
        $this->response->html($contentId, $content);

        // Set onclick handlers on toolbar buttons
        $values = pm()->form($this->formId);
        $this->jq('#adminer-main-action-table-save')
            ->click($this->rq()->alter($server, $database, $schema, $table, $values)
            ->confirm("Save changes on table $table?"));
        $this->jq('#adminer-main-action-table-cancel')
            ->click($this->rq()->show($server, $database, $schema, $table));
        $length = jq(".{$this->formId}-column", "#$contentId")->length;
        $this->jq('#adminer-table-column-add')
            ->click($this->cl(Column::class)->rq()->add($server, $database, $schema, $length));
        $index = jq()->attr('data-index');
        $this->jq('.adminer-table-column-add')
            ->click($this->cl(Column::class)->rq()->add($server, $database, $schema, $length, $index));
        $this->jq('.adminer-table-column-del')
            ->click($this->cl(Column::class)->rq()->setForDelete($server, $database, $schema, $index));

        return $this->response;
    }

    /**
     * Create a new table
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The database schema
     * @param array  $values      The table values
     *
     * @return Response
     */
    public function create(string $server, string $database, string $schema, array $values)
    {
        $values = array_merge($this->defaults, $values);

        $result = $this->dbAdmin->createTable($server, $database, $schema, $values);
        if(!$result['success'])
        {
            $this->response->dialog->error($result['error']);
            return $this->response;
        }

        $this->show($server, $database, $schema, $values['name']);
        $this->response->dialog->success($result['message']);
        return $this->response;
    }

    /**
     * Update a given table
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $table       The table name
     * @param string $schema      The database schema
     * @param array  $values      The table values
     *
     * @return Response
     */
    public function alter(string $server, string $database, string $schema, string $table, array $values)
    {
        $values = array_merge($this->defaults, $values);

        $result = $this->dbAdmin->alterTable($server, $database, $schema, $table, $values);
        if(!$result['success'])
        {
            $this->response->dialog->error($result['error']);
            return $this->response;
        }

        $this->show($server, $database, $schema, $values['name']);
        $this->response->dialog->success($result['message']);
        return $this->response;
    }

    /**
     * Drop a given table
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The database schema
     * @param string $table       The table name
     *
     * @return Response
     */
    public function drop(string $server, string $database, string $schema, string $table): Response
    {
        $result = $this->dbAdmin->dropTable($server, $database, $schema, $table);
        if(!$result['success'])
        {
            $this->response->dialog->error($result['error']);
            return $this->response;
        }

        $this->cl(Database::class)->showTables($server, $database, $schema);
        $this->response->dialog->success($result['message']);
        return $this->response;
    }
}

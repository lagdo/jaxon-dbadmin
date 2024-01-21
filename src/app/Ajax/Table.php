<?php

namespace Lagdo\DbAdmin\App\Ajax;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Table\Column;
use Lagdo\DbAdmin\App\Ajax\Table\Select;
use Lagdo\DbAdmin\App\Ajax\Table\Query;
use Lagdo\DbAdmin\App\CallableClass;

use Exception;

use function array_merge;
use function is_array;
use function Jaxon\jq;
use function Jaxon\pm;

/**
 * @databag selection
 */
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
     * Show the select page for a given table
     *
     * @after('call' => 'showBreadcrumbs')
     *
     * @param string $table       The table name
     *
     * @return Response
     */
    public function select(string $table): Response
    {
        // Save the table name in tha databag and show the select page.
        [$server, $database, $schema] = $this->bag('selection')->get('db');
        $this->bag('selection')->set('db', [$server, $database, $schema, $table]);

        return $this->cl(Select::class)->show();
    }

    /**
     * Show detailed info of a given table
     *
     * @after('call' => 'showBreadcrumbs')
     *
     * @param string $table       The table name
     *
     * @return Response
     */
    public function show(string $table): Response
    {
        [$server, $database, $schema] = $this->bag('selection')->get('db');
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
        if(is_array($indexesInfo))
        {
            $this->showTab($indexesInfo, 'tab-content-indexes');
        }

        // Show foreign keys
        $foreignKeysInfo = $this->dbAdmin->getTableForeignKeys($server, $database, $schema, $table);
        if(is_array($foreignKeysInfo))
        {
            $this->showTab($foreignKeysInfo, 'tab-content-foreign-keys');
        }

        // Show triggers
        $triggersInfo = $this->dbAdmin->getTableTriggers($server, $database, $schema, $table);
        if(is_array($triggersInfo))
        {
            $this->showTab($triggersInfo, 'tab-content-triggers');
        }

        $this->bag('selection')->set('db', [$server, $database, $schema, $table]);

        // Set onclick handlers on toolbar buttons
        $this->jq('#adminer-main-action-edit-table')->click($this->rq()->edit($table));
        $this->jq('#adminer-main-action-drop-table')->click($this->rq()->drop($table)
            ->confirm("Drop table $table?"));
        $this->jq('#adminer-main-action-select-table')->click($this->cl(Select::class)->rq()->show());
        $this->jq('#adminer-main-action-insert-table')->click($this->cl(Query::class)->rq()->showInsert());

        return $this->response;
    }

    /**
     * Create a new table
     *
     * @after('call' => 'showBreadcrumbs')
     *
     * @return Response
     */
    public function add(): Response
    {
        [$server, $database, $schema] = $this->bag('selection')->get('db');
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
        $this->jq('#adminer-main-action-table-save')->click($this->rq()->create($values)->when($length));
        $this->jq('#adminer-main-action-table-cancel')->click($this->cl(Database::class)->rq()->showTables());
        $this->jq('#adminer-table-column-add')->click($this->cl(Column::class)->rq()->add($length));

        return $this->response;
    }

    /**
     * Update a given table
     *
     * @after('call' => 'showBreadcrumbs')
     *
     * @param string $table       The table name
     *
     * @return Response
     */
    public function edit(string $table): Response
    {
        [$server, $database, $schema] = $this->bag('selection')->get('db');
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
        $this->jq('#adminer-main-action-table-save')->click($this->rq()->alter($table, $values)
            ->confirm("Save changes on table $table?"));
        $this->jq('#adminer-main-action-table-cancel')->click($this->rq()->show($table));
        $length = jq(".{$this->formId}-column", "#$contentId")->length;
        $this->jq('#adminer-table-column-add')->click($this->cl(Column::class)->rq()->add($length));
        $index = jq()->attr('data-index');
        $this->jq('.adminer-table-column-add')->click($this->cl(Column::class)->rq()->add($length, $index));
        $this->jq('.adminer-table-column-del')->click($this->cl(Column::class)->rq()->setForDelete($index));

        return $this->response;
    }

    /**
     * Create a new table
     *
     * @param array  $values      The table values
     *
     * @return Response
     */
    public function create(array $values)
    {
        $values = array_merge($this->defaults, $values);

        [$server, $database, $schema] = $this->bag('selection')->get('db');
        $result = $this->dbAdmin->createTable($server, $database, $schema, $values);
        if(!$result['success'])
        {
            $this->response->dialog->error($result['error']);
            return $this->response;
        }

        $this->show($values['name']);
        $this->response->dialog->success($result['message']);
        return $this->response;
    }

    /**
     * Update a given table
     *
     * @param string $table       The table name
     * @param array  $values      The table values
     *
     * @return Response
     */
    public function alter(string $table, array $values)
    {
        $values = array_merge($this->defaults, $values);

        [$server, $database, $schema] = $this->bag('selection')->get('db');
        $result = $this->dbAdmin->alterTable($server, $database, $schema, $table, $values);
        if(!$result['success'])
        {
            $this->response->dialog->error($result['error']);
            return $this->response;
        }

        $this->show($values['name']);
        $this->response->dialog->success($result['message']);
        return $this->response;
    }

    /**
     * Drop a given table
     *
     * @param string $table       The table name
     *
     * @return Response
     */
    public function drop(string $table): Response
    {
        [$server, $database, $schema] = $this->bag('selection')->get('db');
        $result = $this->dbAdmin->dropTable($server, $database, $schema, $table);
        if(!$result['success'])
        {
            $this->response->dialog->error($result['error']);
            return $this->response;
        }

        $this->cl(Database::class)->showTables();
        $this->response->dialog->success($result['message']);
        return $this->response;
    }
}

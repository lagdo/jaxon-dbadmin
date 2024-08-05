<?php

namespace Lagdo\DbAdmin\App\Ajax\Db;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Db\Table\Column;
use Lagdo\DbAdmin\App\Ajax\Db\Table\Select;
use Lagdo\DbAdmin\App\Ajax\Db\Table\Query;
use Lagdo\DbAdmin\App\CallableDbClass;
use Lagdo\DbAdmin\App\Ajax\Page\Content;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function array_merge;
use function is_array;
use function Jaxon\jq;

class Table extends CallableDbClass
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

        $content = $this->ui->mainContent($this->renderMainContent());
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
        $this->bag('dbadmin')->set('db.table', $table);

        return $this->cl(Select::class)->render();
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
        // Save the table name in tha databag.
        $this->bag('dbadmin')->set('db.table', $table);

        $tableInfo = $this->db->getTableInfo($table);
        // Make table info available to views
        $this->view()->shareValues($tableInfo);

        // Test the data bag

        // Set main menu buttons
        $this->cl(PageActions::class)->showTable($table);

        $content = $this->ui->mainDbTable($tableInfo['tabs']);
        $this->cl(Content::class)->showHtml($content);

        // Show fields
        $fieldsInfo = $this->db->getTableFields($table);
        $this->showTab($fieldsInfo, 'tab-content-fields');

        // Show indexes
        $indexesInfo = $this->db->getTableIndexes($table);
        if(is_array($indexesInfo))
        {
            $this->showTab($indexesInfo, 'tab-content-indexes');
        }

        // Show foreign keys
        $foreignKeysInfo = $this->db->getTableForeignKeys($table);
        if(is_array($foreignKeysInfo))
        {
            $this->showTab($foreignKeysInfo, 'tab-content-foreign-keys');
        }

        // Show triggers
        $triggersInfo = $this->db->getTableTriggers($table);
        if(is_array($triggersInfo))
        {
            $this->showTab($triggersInfo, 'tab-content-triggers');
        }

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
        $tableData = $this->db->getTableData();
        // Make data available to views
        $this->view()->shareValues($tableData);

        // Set main menu buttons
        $this->cl(PageActions::class)->addTable($this->formId);

        $content = $this->ui->tableForm($this->formId, $tableData['support'],
            $tableData['engines'], $tableData['collations']);
        $this->cl(Content::class)->showHtml($content);

        // Set onclick handlers on toolbar buttons
        $length = jq(".{$this->formId}-column", "#adminer-database-content")->length;
        $this->jq('#adminer-table-column-add')->click($this->rq(Column::class)->add($length));

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
        $tableData = $this->db->getTableData($table);
        // Make data available to views
        $this->view()->shareValues($tableData);

        // Set main menu buttons
        $this->cl(PageActions::class)->editTable($table, $this->formId);

        $contentId = 'adminer-database-content';
        $editedTable = [
            'name' => $tableData['table']->name,
            'engine' => $tableData['table']->engine,
            'collation' => $tableData['table']->collation,
            'comment' => $tableData['table']->comment,
        ];
        $content = $this->ui->tableForm($this->formId, $tableData['support'], $tableData['engines'],
            $tableData['collations'], $tableData['unsigned'] ?? [], $tableData['foreignKeys'],
            $tableData['options'], $editedTable, $tableData['fields']);
        $this->cl(Content::class)->showHtml($content);

        // Set onclick handlers on toolbar buttons
        $length = jq(".{$this->formId}-column", "#$contentId")->length;
        $this->jq('#adminer-table-column-add')->click($this->rq(Column::class)->add($length));
        $index = jq()->attr('data-index');
        $this->jq('.adminer-table-column-add')->click($this->rq(Column::class)->add($length, $index));
        $this->jq('.adminer-table-column-del')->click($this->rq(Column::class)->setForDelete($index));

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

        $result = $this->db->createTable($values);
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

        $result = $this->db->alterTable($table, $values);
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
        $result = $this->db->dropTable($table);
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

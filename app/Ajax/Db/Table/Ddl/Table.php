<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table\Ddl;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Db\Table\Component;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function array_merge;
use function is_array;

class Table extends Component
{
    /**
     * @var array
     */
    private $tableInfo;

    /**
     * Show detailed info of a given table
     *
     * @after showBreadcrumbs
     *
     * @param string $table       The table name
     *
     * @return Response
     */
    public function table(string $table): Response
    {
        // Save the table name in the databag.
        $this->bag('dbadmin')->set('db.table.name', $table);

        return $this->render();
    }

    /**
     * @inheritDoc
     */
    protected function before()
    {
        $table = $this->bag('dbadmin')->get('db.table.name');

        // Set main menu buttons
        $this->cl(PageActions::class)->showTable($table);

        $this->tableInfo = $this->db->getTableInfo($table);
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui->mainDbTable($this->tableInfo['tabs']);
    }

    /**
     * @inheritDoc
     */
    protected function after()
    {
        $table = $this->bag('dbadmin')->get('db.table.name');

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
    }

    /**
     * Display the content of a tab
     *
     * @param array  $tableData The data to be displayed in the view
     * @param string $tabId     The tab container id
     *
     * @return void
     */
    private function showTab(array $tableData, string $tabId)
    {
        $content = $this->ui->mainContent(array_merge($this->tableInfo, $tableData));
        $this->response->html($tabId, $content);
    }
}

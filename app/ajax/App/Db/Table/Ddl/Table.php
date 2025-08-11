<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Ddl;

use Lagdo\DbAdmin\Ajax\App\Db\Table\MainComponent;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dml\Insert;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Select;
use Lagdo\DbAdmin\Ajax\App\Page\PageActions;

use function array_merge;
use function is_array;

class Table extends MainComponent
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
     * @return void
     */
    public function show(string $table): void
    {
        // Save the table name in the databag.
        $this->bag('dbadmin')->set('db.table.name', $table);

        $this->render();
    }

    /**
     * Print links after select heading
     * Copied from selectLinks() in adminer.inc.php
     *
     * @param bool $new New item options, false for no new item
     *
     * @return array
     */
    // protected function getTableLinks(bool $new = true): array
    // {
    //     $links = [
    //         'select' => $this->trans()->lang('Select data'),
    //     ];
    //     if ($this->db()->support('table') || $this->db()->support('indexes')) {
    //         $links['table'] = $this->trans()->lang('Show structure');
    //     }
    //     if ($this->db()->support('table')) {
    //         $links['alter'] = $this->trans()->lang('Alter table');
    //     }
    //     if ($new) {
    //         $links['edit'] = $this->trans()->lang('New item');
    //     }
    //     // $links['docs'] = \doc_link([$this->db()->jush() => $this->db()->tableHelp($name)], '?');

    //     return $links;
    // }

    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        $table = $this->getTableName();

        // Set main menu buttons
        // $actions = $this->getTableLinks();

        // $actions = [
        //     'create' => $this->trans()->lang('Alter indexes'),
        // ];

        // // From table.inc.php
        // $actions = [
        //     $this->trans()->lang('Add foreign key'),
        // ];

        // $actions = [
        //     $this->trans()->lang('Add trigger'),
        // ];

        $actions = [
            'edit-table' => [
                'title' => $this->trans()->lang('Alter table'),
                'handler' => $this->rq(Alter::class)->render(),
            ],
            'drop-table' => [
                'title' => $this->trans()->lang('Drop table'),
                'handler' => $this->rq()->drop()->confirm("Drop table $table?"),
            ],
            'select-table' => [
                'title' => $this->trans()->lang('Select'),
                'handler' => $this->rq(Select::class)->show($table),
            ],
            'insert-table' => [
                'title' => $this->trans()->lang('New item'),
                'handler' => $this->rq(Insert::class)->show(),
            ],
        ];
        $this->cl(PageActions::class)->show($actions);

        $this->tableInfo = $this->db()->getTableInfo($table);
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui()->mainDbTable($this->tableInfo['tabs']);
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $table = $this->getTableName();

        // Show fields
        $fieldsInfo = $this->db()->getTableFields($table);
        $this->showTab($fieldsInfo, 'tab-content-fields');

        // Show indexes
        $indexesInfo = $this->db()->getTableIndexes($table);
        if(is_array($indexesInfo))
        {
            $this->showTab($indexesInfo, 'tab-content-indexes');
        }

        // Show foreign keys
        $foreignKeysInfo = $this->db()->getTableForeignKeys($table);
        if(is_array($foreignKeysInfo))
        {
            $this->showTab($foreignKeysInfo, 'tab-content-foreign-keys');
        }

        // Show triggers
        $triggersInfo = $this->db()->getTableTriggers($table);
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
    private function showTab(array $tableData, string $tabId): void
    {
        $content = $this->ui()->mainContent(array_merge($this->tableInfo, $tableData));
        $this->response->html($tabId, $content);
    }
}

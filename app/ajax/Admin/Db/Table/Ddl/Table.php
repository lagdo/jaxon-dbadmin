<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl;

use Jaxon\Attributes\Attribute\After;
use Lagdo\DbAdmin\Ajax\Admin\Db\Database\Tables;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\MainComponent;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dml\Insert;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\Select;
use Lagdo\DbAdmin\Ajax\Admin\Page\PageActions;

use function is_array;

class Table extends MainComponent
{
    /**
     * @var array
     */
    private $metadata;

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
        $table = $this->getCurrentTable();

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
            'select-table' => [
                'title' => $this->trans()->lang('Select'),
                'handler' => $this->rq(Select::class)->show($table),
            ],
            'insert-table' => [
                'title' => $this->trans()->lang('New item'),
                'handler' => $this->rq(Insert::class)->show(false),
            ],
            'edit-table' => [
                'title' => $this->trans()->lang('Alter table'),
                'handler' => $this->rq(Alter::class)->render(),
            ],
            'drop-table' => [
                'title' => $this->trans()->lang('Drop table'),
                'handler' => $this->rq(TableFunc::class)->drop($table)
                    ->confirm($this->trans->lang('Drop table %s?', $table)),
            ],
            'tables-back' => [
                'title' => $this->trans()->lang('Back'),
                'handler' => $this->rq(Tables::class)->show(),
            ],
        ];
        $this->cl(PageActions::class)->show($actions);

        $this->metadata = $this->db()->getTableInfo($table);
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->tableUi->mainDbTable($this->metadata['tabs']);
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
        $content = $this->tableUi->pageContent([...$this->metadata, ...$tableData]);
        $this->response()->html($tabId, $content);
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $table = $this->getCurrentTable();

        // Show fields
        $fields = $this->db()->getTableFields($table);
        $this->showTab($fields, $this->tabId('tab-content-fields'));

        // Show indexes
        $indexes = $this->db()->getTableIndexes($table);
        if(is_array($indexes))
        {
            $this->showTab($indexes, $this->tabId('tab-content-indexes'));
        }

        // Show foreign keys
        $foreignKeys = $this->db()->getTableForeignKeys($table);
        if(is_array($foreignKeys))
        {
            $this->showTab($foreignKeys, $this->tabId('tab-content-foreign-keys'));
        }

        // Show triggers
        $triggers = $this->db()->getTableTriggers($table);
        if(is_array($triggers))
        {
            $this->showTab($triggers, $this->tabId('tab-content-triggers'));
        }
    }

    /**
     * Show detailed info of a given table
     *
     * @param string $table       The table name
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function show(string $table): void
    {
        // Save the table name in the databag.
        $this->setCurrentTable($table);

        $this->render();
    }
}

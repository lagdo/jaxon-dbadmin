<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl;

use Jaxon\Attributes\Attribute\After;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\Tables;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\MainComponent;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dml\Insert;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\Select;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;

class Table extends MainComponent
{
    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        $table = $this->getCurrentTable();
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

        $this->set('metadata', $this->db()->getTableInfo($table));
    }

    /**
     * @inheritDoc
     */
    protected function content(): string
    {
        $metadata = $this->get('metadata');
        return $this->tableUi->mainDbTable($metadata['tabs']);
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
        $metadata = $this->get('metadata');
        $content = $this->tableUi->pageContent([...$metadata, ...$tableData]);
        $this->response()->html($tabId, $content);
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $table = $this->getCurrentTable();

        // Show columns
        $columns = $this->db()->getTableColumns($table);
        $this->showTab($columns, $this->tabId('tab-content-columns'));

        // Show indexes
        if(($indexes = $this->db()->getTableIndexes($table)) !== null)
        {
            $this->showTab($indexes, $this->tabId('tab-content-indexes'));
        }

        // Show foreign keys
        if(($foreignKeys = $this->db()->getTableForeignKeys($table)) !== null)
        {
            $this->showTab($foreignKeys, $this->tabId('tab-content-foreign-keys'));
        }

        // Show triggers
        if(($triggers = $this->db()->getTableTriggers($table)) !== null)
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

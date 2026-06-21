<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Database;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\Create;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\Table;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dml\SqlCodeFunc;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\TableFunc;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\Select;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;

class Tables extends MainComponent
{
    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        $this->activateDatabaseSectionMenu('tables');

        // Set main menu buttons
        $this->cl(PageActions::class)->show([
            'add-table' => [
                'title' => $this->trans()->lang('Create table'),
                'handler' => $this->rq(Create::class)->render(),
            ],
        ]);
    }

    /**
     * Show the tables of a given database
     *
     * @return void
     */
    public function show(): void
    {
        $tablesInfo = $this->db()->getTables();

        foreach($tablesInfo['details'] as $name => $detail) {
            $detail->menus = [[
                'label' => $this->trans->lang('Select'),
                'handler' => $this->rq(Select::class)->show($name),
            ], [
                'label' => $this->trans->lang('Show'),
                'handler' => $this->rq(Table::class)->show($name),
            ], [
                'label' => $this->trans->lang('Drop query'),
                'handler' => $this->rq(SqlCodeFunc::class)->showDropTableQuery($name),
            ], [
                'label' => $this->trans->lang('Drop'),
                'handler' => $this->rq(TableFunc::class)->drop($name)
                    ->confirm($this->trans->lang('Drop table %s?', $name)),
            ]];
        }

        $this->showSection($tablesInfo, 'table');

        // Set onclick handlers on table checkbox
        $this->response()->jo('jaxon.dbadmin')
            ->selectTableCheckboxes(...$this->ui()->contentIds('table'));
    }
}

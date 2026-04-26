<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Database;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\Create;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\Table;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\TableFunc;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\Select;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;

use function array_keys;
use function array_map;

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
        $details = $tablesInfo['details'];

        // Add links, classes and data values to table names. The $details['name']
        // value is wrapped into a <div>, the cannot be used as param for calls.
        $tablesInfo['details'] = array_map(fn(array $detail, string $table) => [
            ...$detail,
            'menu' => $this->ui()->buttonMenu([[
                'label' => $this->trans->lang('Select'),
                'handler' => $this->rq(Select::class)->show($table),
            ], [
                'label' => $this->trans->lang('Show'),
                'handler' => $this->rq(Table::class)->show($table),
            ], [
                'label' => $this->trans->lang('Drop'),
                'handler' => $this->rq(TableFunc::class)->drop($table)
                    ->confirm($this->trans->lang('Drop table %s?', $table)),
            ]]),
        ], $details, array_keys($details));

        $this->showSection($tablesInfo, 'table');

        // Set onclick handlers on table checkbox
        $this->response()->jo('jaxon.dbadmin')
            ->selectTableCheckboxes(...$this->ui()->contentIds('table'));
    }
}

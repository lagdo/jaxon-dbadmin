<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Database;

use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Create;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Table;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\Select;
use Lagdo\DbAdmin\Ajax\Admin\Page\PageActions;

use function array_map;
use function Jaxon\jq;

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
                'handler' => $this->rq(Create::class)->show(),
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

        // Add links, classes and data values to table names.
        foreach($tablesInfo['details'] as &$detail) {
            $tableName = $detail['name'];
            $detail['menu'] = $this->ui()->tableMenu([[
                'label' => $this->trans->lang('Show'),
                'handler' => $this->rq(Table::class)->show($tableName),
            ], [
                'label' => $this->trans->lang('Select'),
                'handler' => $this->rq(Select::class)->show($tableName),
            ]]);
        }

        $this->showSection($tablesInfo, 'table');
        // Set onclick handlers on table checkbox
        $this->response->jo('jaxon.dbadmin')->selectTableCheckboxes('table');
    }
}

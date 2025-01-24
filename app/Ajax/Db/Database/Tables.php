<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Lagdo\DbAdmin\App\Ajax\Db\Table\Ddl\Create;
use Lagdo\DbAdmin\App\Ajax\Db\Table\Ddl\Table;
use Lagdo\DbAdmin\App\Ajax\Db\Table\Dql\Select;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function array_map;
use function Jaxon\jq;

class Tables extends ContentComponent
{
    /**
     * @inheritDoc
     */
    protected function before()
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
    public function show()
    {
        $tablesInfo = $this->db()->getTables();

        $table = jq()->parent()->attr('data-table-name');
        $select = $tablesInfo['select'];
        // Add links, classes and data values to table names.
        $tablesInfo['details'] = array_map(function($detail) use($table, $select) {
            $tableName = $detail['name'];
            $detail['show'] = [
                'label' => $tableName,
                'props' => [
                    'data-table-name' => $tableName,
                ],
                'handler' => $this->rq(Table::class)->show($table),
            ];
            $detail['select'] = [
                'label' => $select,
                'props' => [
                    'data-table-name' => $tableName,
                ],
                'handler' => $this->rq(Select::class)->show($table, true),
            ];
            return $detail;
        }, $tablesInfo['details']);

        $this->showSection($tablesInfo, 'table');
        // Set onclick handlers on table checkbox
        // $this->response->js('jaxon.dbadmin')->selectTableCheckboxes('table');
    }
}

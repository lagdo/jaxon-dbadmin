<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Db\Table;
use Lagdo\DbAdmin\App\Ajax\Menu\Actions as MenuActions;
use Lagdo\DbAdmin\App\Ajax\Page\Content;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function array_map;
use function Jaxon\jq;

class Tables extends Component
{
    /**
     * @var string
     */
    protected $overrides = Content::class;

    /**
     * Show the tables of a given database
     *
     * @return Response
     */
    public function refresh(): Response
    {
        // Side menu actions
        $this->cl(MenuActions::class)->database('tables');
        // Set main menu buttons
        $this->cl(PageActions::class)->dbTables();

        $tablesInfo = $this->db->getTables();

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
                'handler' => $this->rq(Table::class)->select($table),
            ];
            return $detail;
        }, $tablesInfo['details']);

        $this->showSection($tablesInfo, 'table');
        // Set onclick handlers on table checkbox
        $this->response->js('jaxon.dbadmin')->selectTableCheckboxes('table');

        return $this->response;
    }
}

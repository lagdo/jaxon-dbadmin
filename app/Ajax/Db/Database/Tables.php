<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Jaxon\Response\Response;

use function Jaxon\jq;

class Tables extends Component
{
    /**
     * Show the tables of a given database
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-table', 'adminer-database-menu'])
     *
     * @return Response
     */
    public function update(): Response
    {
        $tablesInfo = $this->db->getTables();

        $tableNameClass = 'adminer-table-name';
        $select = $tablesInfo['select'];
        // Add links, classes and data values to table names.
        $tablesInfo['details'] = \array_map(function($detail) use($tableNameClass, $select) {
            $detail['name'] = [
                'label' => '<a class="name" href="javascript:void(0)">' . $detail['name'] . '</a>' .
                    '&nbsp;&nbsp;(<a class="select" href="javascript:void(0)">' . $select . '</a>)',
                'props' => [
                    'class' => $tableNameClass,
                    'data-name' => $detail['name'],
                ],
            ];
            return $detail;
        }, $tablesInfo['details']);

        $actions = [
            [$this->trans->lang('Create table'), $this->rq(Table::class)->add()],
        ];
        $checkbox = 'table';
        $this->showSection($tablesInfo, ['checkbox' => $checkbox], $actions);

        // Set onclick handlers on table checkbox
        $this->response->call("jaxon.dbadmin.selectTableCheckboxes", $checkbox);
        // Set onclick handlers on table names
        $table = jq()->parent()->attr('data-name');
        $this->jq('.' . $tableNameClass . '>a.name', '#' . $this->package->getDbContentId())
            ->click($this->rq(Table::class)->show($table));
        $this->jq('.' . $tableNameClass . '>a.select', '#' . $this->package->getDbContentId())
            ->click($this->rq(Table::class)->select($table));

        return $this->response;
    }
}

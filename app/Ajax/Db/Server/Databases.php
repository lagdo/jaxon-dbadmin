<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Server;

use Jaxon\Response\AjaxResponse;
use Lagdo\DbAdmin\App\Ajax\Db\Database\Database;
use Lagdo\DbAdmin\App\Ajax\Menu\DbList;
use Lagdo\DbAdmin\App\Ajax\Menu\SchemaList;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function Jaxon\jq;

class Databases extends Component
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        // Add checkboxes to database table
        $checkbox = 'database';
        return $this->ui->mainContent($this->renderMainContent(['checkbox' => $checkbox]), $checkbox);
    }

    /**
     * Show the database dropdown list.
     *
     * @return array
     */
    protected function showDatabaseMenu(): array
    {
        // Access to servers is forbidden. Show the first database.
        $databasesInfo = $this->db->getDatabases();

        // Make databases info available to views
        $this->view()->shareValues($databasesInfo);

        // Set the database dropdown list
        $this->cl(DbList::class)->showDatabases($databasesInfo['databases']);

        // Clear schema list
        $this->cl(SchemaList::class)->clear();

        return $databasesInfo;
    }

    /**
     * Show the databases of a server
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-databases', 'adminer-database-menu'])
     *
     * @return AjaxResponse
     */
    public function update(): AjaxResponse
    {
        $databasesInfo = $this->showDatabaseMenu();

        $dbNameClass = 'adminer-database-name';
        $dbDropClass = 'adminer-database-drop';
        // Add links, classes and data values to database names.
        $details = \array_map(function($detail) use($dbNameClass, $dbDropClass) {
            $name = $detail['name'];
            $detail['name'] = [
                'label' => '<a href="javascript:void(0)">' . $name . '</a>',
                'props' => [
                    'class' => $dbNameClass,
                    'data-name' => $name,
                ],
            ];
            $detail['drop'] = [
                'label' => '<a href="javascript:void(0)">Drop</a>',
                'props' => [
                    'class' => $dbDropClass,
                    'data-name' => $name,
                ],
            ];
            return $detail;
        }, $databasesInfo['details']);
        $this->view()->share('details', $details);

        // Set main menu buttons
        $actions = [
            [$this->trans->lang('Create database'), $this->rq(Database::class)->add()],
        ];
        $this->cl(PageActions::class)->update($actions);

        $this->render();

        // Set onclick handlers on table checkbox
        $checkbox = 'database';
        $this->response->call("jaxon.dbadmin.selectTableCheckboxes", $checkbox);

        // Set onclick handlers on database names
        $database = jq()->parent()->attr('data-name');
        $this->jq('.' . $dbNameClass . '>a', '#' . $this->package->getDbContentId())
            ->click($this->rq(Database::class)->select($database));

        // Set onclick handlers on database drop
        $database = jq()->parent()->attr('data-name');
        $this->jq('.' . $dbDropClass . '>a', '#' . $this->package->getDbContentId())
            ->click($this->rq(Database::class)->drop($database)
            ->confirm("Delete database {1}?", $database));

        return $this->response;
    }
}

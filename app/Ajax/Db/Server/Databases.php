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
     * @var array
     */
    private $pageContent;

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        // Add checkboxes to database table
        $this->pageContent['checkbox'] = 'database';
        return $this->ui->mainContent($this->pageContent, $this->pageContent['checkbox']);
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
        // Set main menu buttons
        $this->cl(PageActions::class)->databases();

        // Access to servers is forbidden. Show the first database.
        $this->pageContent = $this->db->getDatabases();

        // Set the database dropdown list
        $this->cl(DbList::class)->showDatabases($this->pageContent['databases']);

        // Clear schema list
        $this->cl(SchemaList::class)->clear();

        $dbNameClass = 'adminer-database-name';
        $dbDropClass = 'adminer-database-drop';
        // Add links, classes and data values to database names.
        $this->pageContent['details'] = \array_map(function($detail) use($dbNameClass, $dbDropClass) {
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
        }, $this->pageContent['details']);

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

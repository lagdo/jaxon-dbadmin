<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Server;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Db\Database\Database;
use Lagdo\DbAdmin\App\Ajax\Menu\Actions as MenuActions;
use Lagdo\DbAdmin\App\Ajax\Menu\Server\Databases as MenuDatabases;
use Lagdo\DbAdmin\App\Ajax\Menu\Database\Schemas as MenuSchemas;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function array_map;
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
        return $this->ui->mainContent($this->pageContent, 'database');
    }

    /**
     * Show the databases of a server
     *
     * @return Response
     */
    public function refresh(): Response
    {
        // Access to servers is forbidden. Show the first database.
        $this->pageContent = $this->db->getDatabases();

        // Side menu actions
        $this->cl(MenuActions::class)->server('databases');
        // Set main menu buttons
        $this->cl(PageActions::class)->databases();
        // Set the database dropdown list
        $this->cl(MenuDatabases::class)->showDatabases($this->pageContent['databases']);
        // Clear schema list
        $this->cl(MenuSchemas::class)->clear();

        $database = jq()->parent()->attr('data-database-name');
        // Add links, classes and data values to database names.
        $this->pageContent['details'] = array_map(function($detail) use($database) {
            $databaseName = $detail['name'];
            $detail['select'] = [
                'label' => $databaseName,
                'props' => [
                    'data-database-name' => $databaseName,
                ],
                'handler' => $this->rq(Database::class)->select($database),
            ];
            $detail['drop'] = [
                'label' => 'Drop',
                'props' => [
                    'data-database-name' => $databaseName,
                ],
                'handler' => $this->rq(Database::class)->drop($database)
                    ->confirm("Delete database {1}?", $database),
            ];
            return $detail;
        }, $this->pageContent['details']);

        $this->render();

        // Set onclick handlers on table checkbox
        $checkbox = 'database';
        $this->response->js('jaxon.dbadmin')->selectTableCheckboxes($checkbox);

        return $this->response;
    }
}

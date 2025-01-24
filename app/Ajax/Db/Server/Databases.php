<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Server;

use Lagdo\DbAdmin\App\Ajax\Db\Database\Database;
use Lagdo\DbAdmin\App\Ajax\Menu\Server\Databases as MenuDatabases;
use Lagdo\DbAdmin\App\Ajax\Menu\Database\Schemas as MenuSchemas;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function array_map;
use function Jaxon\jq;

class Databases extends ContentComponent
{
    /**
     * @var array
     */
    private $pageContent;

    /**
     * @inheritDoc
     */
    protected function before()
    {
        $this->activateServerSectionMenu('databases');
        // Set main menu buttons
        $this->cl(PageActions::class)->show([
            'add-database' => [
                'title' => $this->trans()->lang('Create database'),
                'handler' => $this->rq(Database::class)->add(),
            ],
        ]);
        // Clear schema list
        $this->cl(MenuSchemas::class)->clear();
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        // Add checkboxes to database table
        return $this->ui()->mainContent($this->pageContent, 'database');
    }

    /**
     * Show the databases of a server
     *
     * @return void
     */
    public function show()
    {
        // Access to servers is forbidden. Show the first database.
        $this->pageContent = $this->db()->getDatabases();
        // Set the database dropdown list
        $this->cl(MenuDatabases::class)->showDatabases($this->pageContent['databases']);

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
    }
}

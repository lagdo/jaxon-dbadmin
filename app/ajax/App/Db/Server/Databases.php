<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Server;

use Lagdo\DbAdmin\Ajax\App\Db\Database\Database;
use Lagdo\DbAdmin\Ajax\App\Menu\Server\Databases as MenuDatabases;
use Lagdo\DbAdmin\Ajax\App\Menu\Database\Schemas as MenuSchemas;
use Lagdo\DbAdmin\Ajax\App\Page\PageActions;

class Databases extends MainComponent
{
    /**
     * @var array
     */
    private $pageContent;

    /**
     * @inheritDoc
     */
    protected function before(): void
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
        return $this->ui()->pageContent($this->pageContent, 'database');
    }

    /**
     * Show the databases of a server
     * @after showBreadcrumbs
     *
     * @return void
     */
    public function show(): void
    {
        // Access to servers is forbidden. Show the first database.
        $systemAccess = $this->package()->getOption('access.system', false);
        $this->pageContent = $this->db()->getDatabases($systemAccess);
        // Set the database dropdown list
        $this->cl(MenuDatabases::class)->showDatabases($this->pageContent['databases']);

        // Add links, classes and data values to database names.
        foreach($this->pageContent['details'] as &$detail) {
            $databaseName = $detail['name'];
            $detail['menu'] = $this->ui()->tableMenu([[
                'label' => $this->trans->lang('Show'),
                'handler' => $this->rq(Database::class)->select($databaseName),
            ], [
                'label' => $this->trans->lang('Drop'),
                'handler' => $this->rq(Database::class)->drop($databaseName)
                    ->confirm("Delete database {1}?", $databaseName),
            ]]);
        }

        $this->render();

        // Set onclick handlers on table checkbox
        $checkbox = 'database';
        $this->response->jo('jaxon.dbadmin')->selectTableCheckboxes($checkbox);
    }
}

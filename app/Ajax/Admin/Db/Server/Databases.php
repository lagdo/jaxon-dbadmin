<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Server;

use Jaxon\Attributes\Attribute\After;
use Lagdo\DbAdmin\App\Ajax\Admin\DbFunc;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\Database;
use Lagdo\DbAdmin\App\Ajax\Admin\Menu\Server\Databases as MenuDatabases;
use Lagdo\DbAdmin\App\Ajax\Admin\Menu\Database\Schemas as MenuSchemas;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;

class Databases extends MainComponent
{
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
    protected function content(): string
    {
        // Add checkboxes to database table
        return $this->ui()->pageContent($this->get('content'), 'database');
    }

    /**
     * Show the databases of a server
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function show(): void
    {
        $systemAccess = $this->config()->getOption('access.system', false);
        $pageContent = $this->db()->getDatabases($systemAccess);
        // Set the database dropdown list
        $this->cl(MenuDatabases::class)
            ->set('databases', $pageContent['databases'])
            ->render();

        foreach($pageContent['details'] as $name => $detail) {
            $detail->menus = [[
                'label' => $this->trans->lang('Show'),
                'handler' => $this->rq(DbFunc::class)->database($name),
            ], [
                'label' => $this->trans->lang('Drop'),
                'handler' => $this->rq(Database::class)->drop($name)
                    ->confirm("Delete database {1}?", $name),
            ]];
        }

        $this->set('content', $pageContent);
        $this->render();

        // Set onclick handlers on table checkbox
        $this->response()->jo('jaxon.dbadmin')
            ->selectTableCheckboxes(...$this->ui()->contentIds('database'));
    }
}

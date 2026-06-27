<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\Tables;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Server\Server;
use Lagdo\DbAdmin\App\Ajax\Admin\Menu\Database\Schemas as MenuSchemas;
use Lagdo\DbAdmin\App\Ajax\Admin\Menu\Server\Databases as MenuDatabases;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\App\Ajax\Base\FuncComponent;
use Lagdo\DbAdmin\App\Ajax\Base\PageContentFixHeightTrait;

use function count;
use function is_array;

#[Databag('dbadmin.tab')]
#[After('showBreadcrumbs')]
class DbFunc extends FuncComponent
{
    use PageContentFixHeightTrait;

    /**
     * Connect to a database server.
     *
     * @param string $server      The database server id in the package config
     *
     * @return void
     */
    private function connect(string $server): void
    {
        $this->logger()->info('Connecting to server', ['server' => $server]);
        // Set the selected server
        $this->db()->selectDatabase($server);

        $this->cl(Server::class)->connect($server);
    }

    /**
     * @return void
     */
    private function setBags(): void
    {
        if (!$this->bag('dbadmin.app')->get('tab')) {
            $this->bag('dbadmin.app')->set('tab', $this->tab()->app()->current());
        }
        // Initially clear all the tabs.
        $this->setBag('dbadmin.tab', 'editor.names.sv', []);
        $this->setBag('dbadmin.tab', 'editor.names.db', []);
        $this->setBag('dbadmin.tab', 'editor.saved.sv', true);
        $this->setBag('dbadmin.tab', 'editor.saved.db', true);
    }

    /**
     * Connect to a database server.
     *
     * @param string $server      The database server id in the package config
     *
     * @return void
     */
    public function server(string $server): void
    {
        $this->connect($server);
        $this->setBags();
    }

    /**
     * Select a database
     *
     * @param string $database    The database name
     * @param string $schema      The database schema
     *
     * @return void
     */
    public function database(string $database, string $schema = ''): void
    {
        [$server,] = $this->getCurrentDb();
        // Set the selected server.
        $this->db()->selectDatabase($server, $database);

        $systemAccess = $this->config()->getOption('access.system', false);
        $databaseInfo = $this->db()->getDatabaseInfo($systemAccess);

        // Set main menu buttons.
        $this->cl(PageActions::class)->clear();

        // Set the selected entry on database dropdown select.
        $this->cl(MenuDatabases::class)->change($database);

        $schemas = $databaseInfo['schemas'];
        if(is_array($schemas) && count($schemas) > 0 && !$schema)
        {
            $schema = $schemas[0]; // Select the first schema.
            $this->cl(MenuSchemas::class)
                ->set('database', $database)
                ->set('schemas', $schemas)
                ->render();
        }

        // The current schema might have changed. Reselect the database.
        $this->db()->selectDatabase($server, $database, $schema);
        // Save the selection in the databag.
        $this->setCurrentDb([$server, $database, $schema]);

        // Show the database tables.
        $this->cl(Tables::class)->show();
    }
}

<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Server;

use Lagdo\DbAdmin\App\Ajax\Db\Database\Database;
use Lagdo\DbAdmin\App\Ajax\Menu\Database\Command as DatabaseCommand;
use Lagdo\DbAdmin\App\Ajax\Menu\Database\Schemas as MenuSchemas;
use Lagdo\DbAdmin\App\Ajax\Menu\Server\Command as ServerCommand;
use Lagdo\DbAdmin\App\Ajax\Menu\Server\Databases as MenuDatabases;
use Lagdo\DbAdmin\App\Ajax\Page\ServerInfo;
use Lagdo\DbAdmin\App\CallableDbClass;

use function array_values;
use function count;

class Server extends CallableDbClass
{
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
        $this->cl(MenuDatabases::class)->showDatabases($databasesInfo['databases']);

        // Clear schema list
        $this->cl(MenuSchemas::class)->clear();

        return $databasesInfo;
    }

    /**
     * Connect to a db server.
     * The database list will be displayed in the HTML select component.
     *
     * @exclude
     *
     * @param bool $hasServerAccess
     *
     * @return void
     */
    public function connect(bool $hasServerAccess)
    {
        $serverInfo = $this->db->getServerInfo();
        // Make server info available to views
        $this->view()->shareValues($serverInfo);

        $this->cl(ServerInfo::class)->showServer($serverInfo['server'], $serverInfo['user']);

        // Show the server
        $this->cl(ServerCommand::class)->render();
        $this->cl(DatabaseCommand::class)->clear();

        if(!$hasServerAccess)
        {
            $databasesInfo = $this->showDatabaseMenu();
            if(count($databasesInfo['databases']) > 0)
            {
                $database = array_values($databasesInfo['databases'])[0];
                $this->cl(Database::class)->select($database);
            }
            return;
        }

        $this->cl(Databases::class)->show();
    }
}

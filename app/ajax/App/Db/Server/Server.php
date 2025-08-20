<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Server;

use Lagdo\DbAdmin\Ajax\App\Db\Database\Database;
use Lagdo\DbAdmin\Ajax\App\Db\FuncComponent;
use Lagdo\DbAdmin\Ajax\App\Menu\Database\Command as DatabaseCommand;
use Lagdo\DbAdmin\Ajax\App\Menu\Server\Command as ServerCommand;
use Lagdo\DbAdmin\Ajax\App\Menu\Server\Databases as MenuDatabases;
use Lagdo\DbAdmin\Ajax\App\Page\DbConnection;
use Lagdo\DbAdmin\Ajax\App\Sidebar;

use function array_values;
use function count;

class Server extends FuncComponent
{
    /**
     * @return array
     */
    private function getDatabases(): array
    {
        $systemAccess = $this->package()->getOption('access.system', false);
        return $this->db()->getDatabases($systemAccess)['databases'];
    }

    /**
     * Connect to a db server.
     * The database list will be displayed in the HTML select component.
     *
     * @exclude
     *
     * @param string $server
     *
     * @return void
     */
    public function connect(string $server): void
    {
        // Save the selected server in the databag
        $this->bag('dbadmin')->set('db', [$server, '', '']);

        $serverInfo = $this->db()->getServerInfo();

        $this->cl(DbConnection::class)
            ->show($serverInfo['server'], $serverInfo['user']);

        // Refresh the sidebar content
        $this->cl(Sidebar::class)->refresh($server);

        // Always show the database list.
        $databases = $this->getDatabases();
        $this->cl(MenuDatabases::class)->showDatabases($databases);

        $hasServerAccess = $this->package()->getServerAccess($server);
        if($hasServerAccess)
        {
            $this->cl(ServerCommand::class)->render();
            $this->cl(Databases::class)->show();
            return;
        }

        if(count($databases) > 0)
        {
            $this->cl(DatabaseCommand::class)->render();
            $database = array_values($databases)[0];
            $this->cl(Database::class)->select($database);
        }
    }
}

<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Server;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\Database;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\FuncComponent;
use Lagdo\DbAdmin\App\Ajax\Admin\Menu\Database\Command as DatabaseCommand;
use Lagdo\DbAdmin\App\Ajax\Admin\Menu\Server\Command as ServerCommand;
use Lagdo\DbAdmin\App\Ajax\Admin\Menu\Server\Databases as MenuDatabases;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\DbServer;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\DbUser;
use Lagdo\DbAdmin\App\Ajax\Admin\Sidebar;

use function array_values;
use function count;

class Server extends FuncComponent
{
    /**
     * Connect to a db server.
     * The database list will be displayed in the HTML select component.
     *
     * @param string $server
     *
     * @return void
     */
    #[Exclude]
    public function connect(string $server): void
    {
        // Save the selected server in the databag
        $this->setCurrentDb([$server, '', '']);

        $serverInfo = $this->db()->getServerInfo();
        $this->cl(DbUser::class)->show($serverInfo['user']);
        $this->cl(DbServer::class)->show($serverInfo['server']);

        // Refresh the sidebar content
        $this->cl(Sidebar::class)->refresh($server);

        // Always show the database list.
        $systemAccess = $this->config()->getOption('access.system', false);
        $databases = $this->db()->getDatabases($systemAccess)['databases'];
        $this->cl(MenuDatabases::class)->set('databases', $databases)->render();

        $hasServerAccess = $this->config()->getServerAccess($server);
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

<?php

namespace Lagdo\DbAdmin\App\Ajax;

use Lagdo\DbAdmin\App\Component;
use Lagdo\DbAdmin\App\Ajax\Db\Server\Server;

class Admin extends Component
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $servers = $this->package->getOption('servers', []);
        $default = $this->package->getOption('default', '');
        return $this->ui->home($servers, $default);
    }

    /**
     * Connect to a database server.
     *
     * @after showBreadcrumbs
     *
     * @param string $server      The database server id in the package config
     *
     * @return void
     */
    public function server(string $server)
    {
        // Set the selected server
        $this->db->selectDatabase($server);
        // Save the selected server in the databag
        $this->bag('dbadmin')->set('db', [$server, '', '']);

        return $this->cl(Server::class)->connect($this->package->getServerAccess($server));
    }
}

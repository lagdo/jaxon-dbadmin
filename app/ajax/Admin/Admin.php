<?php

namespace Lagdo\DbAdmin\Ajax\Admin;

use Jaxon\Attributes\Attribute\After;
use Lagdo\DbAdmin\Ajax\Component;
use Lagdo\DbAdmin\Ajax\Admin\Db\Server\Server;

class Admin extends Component
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $servers = $this->package()->getOption('servers', []);
        $serverAccess = $this->package()->getOption('access.server', false);
        $default = $this->package()->getOption('default', '');
        return $this->ui()->home($servers, $serverAccess, $default);
    }

    /**
     * Connect to a database server.
     *
     * @param string $server      The database server id in the package config
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function server(string $server): void
    {
        $this->logger()->info('Connecting to server', ['server' => $server]);

        // Set the selected server
        $this->db()->selectDatabase($server);

        $this->cl(Server::class)->connect($server);
    }
}

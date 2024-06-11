<?php

namespace Lagdo\DbAdmin\App\Ajax;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\CallableClass;

class Admin extends CallableClass
{
    /**
     * Connect to a database server.
     *
     * @callback jaxon.dbadmin.callback.server
     * @after showBreadcrumbs
     *
     * @param string $server      The database server id in the package config
     *
     * @return Response
     */
    public function server(string $server): Response
    {
        // Set the selected server
        $this->db->selectDatabase($server);
        // Save the selected server in the databag
        $this->bag('dbadmin')->set('db', [$server, '', '']);

        return $this->cl(Db\Server::class)->connect($this->package->getServerAccess($server));
    }
}

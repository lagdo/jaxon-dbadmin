<?php

namespace Lagdo\DbAdmin\App\Ajax;

use Jaxon\App\Component;
use Jaxon\Response\AjaxResponse;
use Lagdo\DbAdmin\App\Ajax\Db\Server\Server;
use Lagdo\DbAdmin\App\Package;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Ui\PageBuilder;

class Admin extends Component
{
    /**
     * The constructor
     *
     * @param Package $package The DbAdmin package
     * @param DbFacade $db The facade to database functions
     * @param PageBuilder $ui The HTML UI builder
     */
    public function __construct(private Package $package, private DbFacade $db, private PageBuilder $ui)
    {}

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
     * @callback jaxon.dbadmin.callback.server
     * @after showBreadcrumbs
     *
     * @param string $server      The database server id in the package config
     *
     * @return AjaxResponse
     */
    public function server(string $server): AjaxResponse
    {
        // Set the selected server
        $this->db->selectDatabase($server);
        // Save the selected server in the databag
        $this->bag('dbadmin')->set('db', [$server, '', '']);

        return $this->cl(Server::class)->connect($this->package->getServerAccess($server));
    }
}

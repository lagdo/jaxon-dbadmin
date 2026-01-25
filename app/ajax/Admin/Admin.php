<?php

namespace Lagdo\DbAdmin\Ajax\Admin;

use Jaxon\Attributes\Attribute\After;
use Lagdo\DbAdmin\Ajax\Base\FuncComponent;
use Lagdo\DbAdmin\Ajax\Admin\Db\Server\Server;

class Admin extends FuncComponent
{
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

    public function addTab(): void
    {
        // Get the last connected server.
        [$server, ] = $this->currentDb();

        $name = 'app-tab-one';
        $this->bag('dbadmin.tab')->set('current', $name);
        $this->stash()->set('tab.current', $name);
        $this->setupComponent();

        $nav = $this->ui()->tabNavItemHtml('Database tab one');
        $content = $this->ui()->tabContentItemHtml();
        $this->response()->jo('jaxon.dbadmin')->addTab($nav, $content);

        // Connect the new tab to the same last connected server.
        $this->server($server);
    }
}

<?php

namespace Lagdo\DbAdmin\Db\DbAdmin\Server;

use Lagdo\DbAdmin\Db\DbAdmin\AbstractAdmin;
use Exception;

/**
 * Admin server functions
 */
trait ServerTrait
{
    /**
     * The proxy
     *
     * @var ServerAdmin
     */
    protected $serverAdmin = null;

    /**
     * @return AbstractAdmin
     */
    abstract public function admin(): AbstractAdmin;

    /**
     * Connect to a database server
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     *
     * @return void
     */
    abstract public function connect(string $server, string $database = '', string $schema = '');

    /**
     * Set the breadcrumbs items
     *
     * @param array $breadcrumbs
     *
     * @return void
     */
    abstract protected function setBreadcrumbs(array $breadcrumbs);

    /**
     * Get the proxy
     *
     * @param array $options    The server config options
     *
     * @return ServerAdmin
     */
    protected function server(array $options): ServerAdmin
    {
        if (!$this->serverAdmin) {
            $this->serverAdmin = new ServerAdmin($options);
            $this->serverAdmin->init($this->admin());
        }
        return $this->serverAdmin;
    }

    /**
     * Connect to a database server
     *
     * @param string $server    The selected server
     *
     * @return array
     */
    public function getServerInfo(string $server): array
    {
        $this->connect($server);

        $package = $this->admin()->package;
        $this->setBreadcrumbs([$package->getServerName($server)]);

        return $this->server($package->getServerOptions($server))->getServerInfo();
    }

    /**
     * Get the collation list
     *
     * @param string $server    The selected server
     *
     * @return array
     */
    public function getCollations(string $server): array
    {
        $this->connect($server);

        $package = $this->admin()->package;
        return $this->server($package->getServerOptions($server))->getCollations();
    }

    /**
     * Get the database list
     *
     * @param string $server    The selected server
     *
     * @return array
     */
    public function getDatabases(string $server): array
    {
        $this->connect($server);

        $package = $this->admin()->package;
        $this->setBreadcrumbs([$package->getServerName($server), $this->trans->lang('Databases')]);

        return $this->server($package->getServerOptions($server))->getDatabases();
    }

    /**
     * Get the processes
     *
     * @param string $server    The selected server
     *
     * @return array
     */
    public function getProcesses(string $server): array
    {
        $this->connect($server);

        $package = $this->admin()->package;
        $this->setBreadcrumbs([$package->getServerName($server), $this->trans->lang('Process list')]);

        return $this->server($package->getServerOptions($server))->getProcesses();
    }

    /**
     * Get the variables
     *
     * @param string $server    The selected server
     *
     * @return array
     */
    public function getVariables(string $server): array
    {
        $this->connect($server);

        $package = $this->admin()->package;
        $this->setBreadcrumbs([$package->getServerName($server), $this->trans->lang('Variables')]);

        return $this->server($package->getServerOptions($server))->getVariables();
    }

    /**
     * Get the server status
     *
     * @param string $server    The selected server
     *
     * @return array
     */
    public function getStatus(string $server): array
    {
        $this->connect($server);

        $package = $this->admin()->package;
        $this->setBreadcrumbs([$package->getServerName($server), $this->trans->lang('Status')]);

        return $this->server($package->getServerOptions($server))->getStatus();
    }

    /**
     * Create a database
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $collation The database collation
     *
     * @return bool
     */
    public function createDatabase(string $server, string $database, string $collation = ''): bool
    {
        $this->connect($server);

        $package = $this->admin()->package;
        return $this->server($package->getServerOptions($server))->createDatabase($database, $collation);
    }

    /**
     * Drop a database
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     *
     * @return bool
     */
    public function dropDatabase(string $server, string $database): bool
    {
        $this->connect($server);

        $package = $this->admin()->package;
        return $this->server($package->getServerOptions($server))->dropDatabase($database);
    }
}

<?php

namespace Lagdo\DbAdmin\Db\Server;

use Lagdo\DbAdmin\Db\AbstractFacade;
use Exception;

/**
 * Facade to server functions
 */
trait ServerTrait
{
    /**
     * The proxy
     *
     * @var ServerFacade
     */
    protected $serverFacade = null;

    /**
     * @return AbstractFacade
     */
    abstract public function facade(): AbstractFacade;

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
     * @return ServerFacade
     */
    protected function server(array $options): ServerFacade
    {
        if (!$this->serverFacade) {
            $this->serverFacade = new ServerFacade($options);
            $this->serverFacade->init($this->facade());
        }
        return $this->serverFacade;
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

        $package = $this->facade()->package;
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

        $package = $this->facade()->package;
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

        $package = $this->facade()->package;
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

        $package = $this->facade()->package;
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

        $package = $this->facade()->package;
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

        $package = $this->facade()->package;
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

        $package = $this->facade()->package;
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

        $package = $this->facade()->package;
        return $this->server($package->getServerOptions($server))->dropDatabase($database);
    }
}

<?php

namespace Lagdo\DbAdmin\Db\Server;

use Jaxon\Di\Container;

/**
 * Facade to server functions
 */
trait ServerTrait
{
    /**
     * @return Container
     */
    abstract public function di(): Container;

    /**
     * Connect to a database server
     *
     * @return void
     */
    abstract public function connectToServer();

    /**
     * Set the breadcrumbs items
     *
     * @param bool $showDatabase
     * @param array $breadcrumbs
     *
     * @return void
     */
    abstract protected function setBreadcrumbs(bool $showDatabase = false, array $breadcrumbs = []);

    /**
     * Get the proxy
     *
     * @return ServerFacade
     */
    protected function server(): ServerFacade
    {
        return $this->di()->g(ServerFacade::class);
    }

    /**
     * @return array
     */
    public function getServerInfo(): array
    {
        $this->connectToServer();

        $this->setBreadcrumbs();

        return $this->server()->getServerInfo();
    }

    /**
     * Get the collation list
     *
     * @return array
     */
    public function getCollations(): array
    {
        $this->connectToServer();

        return $this->server()->getCollations();
    }

    /**
     * Get the database list
     *
     * @return array
     */
    public function getDatabases(): array
    {
        $this->connectToServer();

        $this->setBreadcrumbs(false, [$this->trans->lang('Databases')]);

        return $this->server()->getDatabases();
    }

    /**
     * Get the processes
     *
     * @return array
     */
    public function getProcesses(): array
    {
        $this->connectToServer();

        $this->setBreadcrumbs(false, [$this->trans->lang('Process list')]);

        return $this->server()->getProcesses();
    }

    /**
     * Get the variables
     *
     * @return array
     */
    public function getVariables(): array
    {
        $this->connectToServer();

        $this->setBreadcrumbs(false, [$this->trans->lang('Variables')]);

        return $this->server()->getVariables();
    }

    /**
     * Get the server status
     *
     * @return array
     */
    public function getStatus(): array
    {
        $this->connectToServer();

        $this->setBreadcrumbs(false, [$this->trans->lang('Status')]);

        return $this->server()->getStatus();
    }

    /**
     * Create a database
     *
     * @param string $database  The database name
     * @param string $collation The database collation
     *
     * @return bool
     */
    public function createDatabase(string $database, string $collation = ''): bool
    {
        $this->connectToServer();

        return $this->server()->createDatabase($database, $collation);
    }

    /**
     * Drop a database
     *
     * @param string $database  The database name
     *
     * @return bool
     */
    public function dropDatabase(string $database): bool
    {
        $this->connectToServer();

        return $this->server()->dropDatabase($database);
    }
}

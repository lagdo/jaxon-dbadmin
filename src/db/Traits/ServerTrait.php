<?php

namespace Lagdo\DbAdmin\Db\Traits;

use Lagdo\DbAdmin\Db\Facades\ServerFacade;

/**
 * Facade to server functions
 */
trait ServerTrait
{
    use AbstractTrait;

    /**
     * Get the facade
     *
     * @return ServerFacade
     */
    protected function serverFacade(): ServerFacade
    {
        return $this->di()->g(ServerFacade::class);
    }

    /**
     * @return array
     */
    public function getServerInfo(): array
    {
        $this->connectToServer();
        return $this->serverFacade()->getServerInfo();
    }

    /**
     * Get the collation list
     *
     * @return array
     */
    public function getCollations(): array
    {
        $this->connectToServer();
        return $this->serverFacade()->getCollations();
    }

    /**
     * Get the database list
     *
     * @return array
     */
    public function getDatabases(): array
    {
        $this->connectToServer();
        $this->bccl()->breadcrumb($this->utils->trans->lang('Databases'));
        return $this->serverFacade()->getDatabases();
    }

    /**
     * Get the processes
     *
     * @return array
     */
    public function getProcesses(): array
    {
        $this->connectToServer();
        $this->bccl()->breadcrumb($this->utils->trans->lang('Process list'));
        return $this->serverFacade()->getProcesses();
    }

    /**
     * Get the variables
     *
     * @return array
     */
    public function getVariables(): array
    {
        $this->connectToServer();
        $this->bccl()->breadcrumb($this->utils->trans->lang('Variables'));
        return $this->serverFacade()->getVariables();
    }

    /**
     * Get the server status
     *
     * @return array
     */
    public function getStatus(): array
    {
        $this->connectToServer();
        $this->bccl()->breadcrumb($this->utils->trans->lang('Status'));
        return $this->serverFacade()->getStatus();
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
        return $this->serverFacade()->createDatabase($database, $collation);
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
        return $this->serverFacade()->dropDatabase($database);
    }
}

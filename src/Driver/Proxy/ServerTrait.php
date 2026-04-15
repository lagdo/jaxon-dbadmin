<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

/**
 * Proxy to server functions
 */
trait ServerTrait
{
    use AbstractProxyTrait;

    /**
     * Get the proxy
     *
     * @return ServerProxy
     */
    protected function serverProxy(): ServerProxy
    {
        return $this->di()->g(ServerProxy::class);
    }

    /**
     * @return array
     */
    public function getServerInfo(): array
    {
        $this->connectToServer();
        return $this->serverProxy()->getServerInfo();
    }

    /**
     * Check if a feature is supported
     *
     * @param string $feature
     *
     * @return bool
     */
    public function support(string $feature): bool
    {
        $this->connectToServer();
        return $this->serverProxy()->support($feature);
    }

    /**
     * Get the collation list
     *
     * @return array
     */
    public function getCollations(): array
    {
        $this->connectToServer();
        return $this->serverProxy()->getCollations();
    }

    /**
     * Get the database list
     *
     * @param bool $schemaAccess
     *
     * @return array
     */
    public function getDatabases(bool $schemaAccess): array
    {
        $this->connectToServer();
        $this->breadcrumbs()->clear()->item($this->utils()->lang('Databases'));
        return $this->serverProxy()->getDatabases($schemaAccess);
    }

    /**
     * Get the processes
     *
     * @return array
     */
    public function getProcesses(): array
    {
        $this->connectToServer();
        $this->breadcrumbs()->clear()->item($this->utils()->lang('Process list'));
        return $this->serverProxy()->getProcesses();
    }

    /**
     * Get the variables
     *
     * @return array
     */
    public function getVariables(): array
    {
        $this->connectToServer();
        $this->breadcrumbs()->clear()->item($this->utils()->lang('Variables'));
        return $this->serverProxy()->getVariables();
    }

    /**
     * Get the server status
     *
     * @return array
     */
    public function getStatus(): array
    {
        $this->connectToServer();
        $this->breadcrumbs()->clear()->item($this->utils()->lang('Status'));
        return $this->serverProxy()->getStatus();
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
        return $this->serverProxy()->createDatabase($database, $collation);
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
        return $this->serverProxy()->dropDatabase($database);
    }
}

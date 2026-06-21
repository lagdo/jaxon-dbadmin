<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Support\Driver\UiDto\ExecResultDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\QueryListDto;

use Exception;

/**
 * Proxy to database functions
 */
trait DatabaseTrait
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
     * Get the proxy
     *
     * @return DatabaseProxy
     */
    protected function databaseProxy()
    {
        return $this->di()->g(DatabaseProxy::class);
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
     * Get the privilege list
     * This feature is available only for MySQL
     *
     * @param string $database  The database name
     *
     * @return array
     */
    public function getPrivileges(string $database = ''): array
    {
        $this->connectToServer();
        $this->breadcrumbs()->clear()->item($this->utils()->lang('Privileges'));
        return $this->serverProxy()->getPrivileges($database);
    }

    /**
     * Get the privileges for a new user
     *
     * @return array
     */
    public function newUserPrivileges(): array
    {
        $this->connectToServer();
        return $this->serverProxy()->newUserPrivileges();
    }

    /**
     * Get the privileges for a new user
     *
     * @param string $user      The user name
     * @param string $host      The host name
     * @param string $database  The database name
     *
     * @return array
     */
    public function getUserPrivileges(string $user, string $host, string $database): array
    {
        $this->connectToServer();
        return $this->serverProxy()->getUserPrivileges($user, $host, $database);
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

    /**
     * Connect to a database server
     *
     * @param bool $schemaAccess
     *
     * @return array
     */
    public function getDatabaseInfo(bool $schemaAccess)
    {
        $this->connectToDatabase();
        $this->breadcrumbs(true);
        return $this->databaseProxy()->getDatabaseInfo($schemaAccess);
    }

    /**
     * Get the tables from a database server
     *
     * @return array
     */
    public function getTables()
    {
        $this->connectToSchema();
        $this->breadcrumbs(true)->item($this->utils()->lang('Tables'));
        return $this->databaseProxy()->getTables();
    }

    /**
     * Get the views from a database server
     *
     * @return array
     */
    public function getViews()
    {
        $this->connectToSchema();
        $this->breadcrumbs(true)->item($this->utils()->lang('Views'));
        return $this->databaseProxy()->getViews();
    }

    /**
     * Get the routines from a given database
     *
     * @return array
     */
    public function getRoutines()
    {
        $this->connectToSchema();
        $this->breadcrumbs(true)->item($this->utils()->lang('Routines'));
        return $this->databaseProxy()->getRoutines();
    }

    /**
     * Get the sequences from a given database
     *
     * @return array
     */
    public function getSequences()
    {
        $this->connectToSchema();
        $this->breadcrumbs(true)->item($this->utils()->lang('Sequences'));
        return $this->databaseProxy()->getSequences();
    }

    /**
     * Get the user types from a given database
     *
     * @return array
     */
    public function getUserTypes()
    {
        $this->connectToSchema();
        $this->breadcrumbs(true)->item($this->utils()->lang('User types'));
        return $this->databaseProxy()->getUserTypes();
    }

    /**
     * Get the events from a given database
     *
     * @return array
     */
    public function getEvents()
    {
        $this->connectToSchema();
        $this->breadcrumbs(true)->item($this->utils()->lang('Events'));
        return $this->databaseProxy()->getEvents();
    }

    /**
     * Get all the columns of all the tables in the database.
     *
     * @return array
     */
    public function getSchemaColumns(): array
    {
        $this->connectToSchema();
        return $this->databaseProxy()->getSchemaColumns();
    }

    /**
     * Get details about a view
     *
     * @param string $view      The view name
     *
     * @return array
     */
    public function getViewInfo(string $view): array
    {
        $this->connectToSchema();
        $this->breadcrumbs(true)
            ->item($this->utils()->lang('Views'))
            ->item("<i><b>$view</b></i>");
        $this->utils()->input->table = $view;
        return $this->databaseProxy()->getViewInfo($view);
    }

    /**
     * Get details about a view
     *
     * @param string $view The view name
     *
     * @return array
     * @throws Exception
     */
    public function getViewColumns(string $view): array
    {
        $this->connectToSchema();
        $this->utils()->input->table = $view;
        return $this->databaseProxy()->getViewColumns($view);
    }

    /**
     * Get the triggers of a view
     *
     * @param string $view      The view name
     *
     * @return array|null
     */
    public function getViewTriggers(string $view): ?array
    {
        $this->connectToSchema();
        $this->utils()->input->table = $view;
        return $this->databaseProxy()->getViewTriggers($view);
    }

    /**
     * Get a view
     *
     * @param string $view The view name
     *
     * @return array
     * @throws Exception
     */
    public function getView(string $view): array
    {
        $this->connectToSchema();
        $this->utils()->input->table = $view;
        return $this->databaseProxy()->getView($view);
    }

    /**
     * Create a view
     *
     * @param array $values The view values
     *
     * @return array
     * @throws Exception
     */
    public function createView(array $values): array
    {
        $this->connectToSchema();
        $this->utils()->input->table = $values['name'];
        return $this->databaseProxy()->createView($values);
    }

    /**
     * Update a view
     *
     * @param string $view The view name
     * @param array $values The view values
     *
     * @return array
     * @throws Exception
     */
    public function updateView(string $view, array $values): array
    {
        $this->connectToSchema();
        $this->utils()->input->table = $view;
        return $this->databaseProxy()->updateView($view, $values);
    }

    /**
     * Get SQL command to drop a view
     *
     * @param string $view
     *
     * @return QueryListDto
     */
    public function getDropViewQueries(string $view): QueryListDto
    {
        $this->connectToSchema();
        return $this->databaseProxy()->getDropViewQueries($view);
    }

    /**
     * Drop a view
     *
     * @param string $view The view name
     *
     * @return ExecResultDto
     */
    public function dropView(string $view): ExecResultDto
    {
        $this->connectToSchema();
        return $this->databaseProxy()->dropView($view);
    }
}

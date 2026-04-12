<?php

namespace Lagdo\DbAdmin\Db\Driver\Proxy;

/**
 * Proxy to database functions
 */
trait DatabaseTrait
{
    use AbstractProxyTrait;

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
}

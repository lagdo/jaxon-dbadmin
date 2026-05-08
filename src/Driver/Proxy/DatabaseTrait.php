<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

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
     * Drop a view
     *
     * @param string $view The view name
     *
     * @return array
     * @throws Exception
     */
    public function dropView(string $view): array
    {
        $this->connectToSchema();
        return $this->databaseProxy()->dropView($view);
    }
}

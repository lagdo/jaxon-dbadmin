<?php

namespace Lagdo\DbAdmin\Db\Query;

use Jaxon\Di\Container;

/**
 * Facade to table query functions
 */
trait QueryTrait
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
    abstract public function connectToSchema();

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
     * @return QueryFacade
     */
    protected function tableQuery(): QueryFacade
    {
        return $this->di()->g(QueryFacade::class);
    }

    /**
     * Get data for insert/update on a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     * @param string $action        The action title
     *
     * @return array
     */
    public function getQueryData(string $table, array $queryOptions = [], string $action = 'New item'): array
    {
        $this->connectToSchema();

        $this->setBreadcrumbs(true, [$this->trans->lang('Tables'), $table, $this->trans->lang($action)]);

        $this->util->input()->table = $table;
        $this->util->input()->values = $queryOptions;
        return $this->tableQuery()->getQueryData($table, $queryOptions);
    }

    /**
     * Insert a new item in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function insertItem(string $table, array $queryOptions): array
    {
        $this->connectToSchema();

        $this->util->input()->table = $table;
        $this->util->input()->values = $queryOptions;
        return $this->tableQuery()->insertItem($table, $queryOptions);
    }

    /**
     * Update one or more items in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function updateItem(string $table, array $queryOptions): array
    {
        $this->connectToSchema();

        $this->util->input()->table = $table;
        $this->util->input()->values = $queryOptions;
        return $this->tableQuery()->updateItem($table, $queryOptions);
    }

    /**
     * Delete one or more items in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function deleteItem(string $table, array $queryOptions): array
    {
        $this->connectToSchema();

        $this->util->input()->table = $table;
        $this->util->input()->values = $queryOptions;
        return $this->tableQuery()->deleteItem($table, $queryOptions);
    }
}

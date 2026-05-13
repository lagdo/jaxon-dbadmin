<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

/**
 * Proxy to table query functions
 */
trait QueryTrait
{
    use AbstractProxyTrait;

    /**
     * Get the proxy
     *
     * @return QueryProxy
     */
    protected function queryProxy(): QueryProxy
    {
        return $this->di()->g(QueryProxy::class);
    }

    /**
     * Get data for insert on a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function getInsertData(string $table, array $queryOptions = []): array
    {
        $this->connectToSchema();
        $this->utils()->setInputTable($table);
        $this->utils()->setInputValues($queryOptions);
        return $this->queryProxy()->getInsertData($table, $queryOptions);
    }

    /**
     * Build the SQL query to insert a new item in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     * @param array  $values        The updated values
     *
     * @return array
     */
    public function getRowInsertQuery(string $table, array $queryOptions, array $values): array
    {
        $this->connectToSchema();
        $this->utils()->setInputTable($table);
        $this->utils()->setInputValues($values);
        return $this->queryProxy()->getRowInsertQuery($table, $queryOptions, $values);
    }

    /**
     * Insert a new item in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     * @param array  $values        The updated values
     *
     * @return array
     */
    public function insertItem(string $table, array $queryOptions, array $values): array
    {
        $this->connectToSchema();
        $this->utils()->setInputTable($table);
        $this->utils()->setInputValues($values);
        return $this->queryProxy()->insertItem($table, $queryOptions, $values);
    }

    /**
     * Get data for update/delete in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function getUpdateData(string $table, array $queryOptions = []): array
    {
        $this->connectToSchema();
        $this->utils()->setInputTable($table);
        $this->utils()->setInputValues($queryOptions);
        return $this->queryProxy()->getUpdateData($table, $queryOptions);
    }

    /**
     * Build the SQL query to update one or more items in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     * @param array  $values        The updated values
     *
     * @return array
     */
    public function getRowUpdateQuery(string $table, array $queryOptions, array $values): array
    {
        $this->connectToSchema();
        $this->utils()->setInputTable($table);
        $this->utils()->setInputValues($values);
        return $this->queryProxy()->getRowUpdateQuery($table, $queryOptions, $values);
    }

    /**
     * Update one or more items in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     * @param array  $values        The updated values
     *
     * @return array
     */
    public function updateItem(string $table, array $queryOptions, array $values): array
    {
        $this->connectToSchema();
        $this->utils()->setInputTable($table);
        $this->utils()->setInputValues($values);
        return $this->queryProxy()->updateItem($table, $queryOptions, $values);
    }

    /**
     * Build the SQL query to delete one or more items in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function getRowDeleteQuery(string $table, array $queryOptions): array
    {
        $this->connectToSchema();
        $this->utils()->setInputTable($table);
        $this->utils()->setInputValues($queryOptions);
        return $this->queryProxy()->getRowDeleteQuery($table, $queryOptions);
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
        $this->utils()->setInputTable($table);
        $this->utils()->setInputValues($queryOptions);
        return $this->queryProxy()->deleteItem($table, $queryOptions);
    }
}

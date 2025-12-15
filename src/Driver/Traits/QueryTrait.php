<?php

namespace Lagdo\DbAdmin\Db\Driver\Traits;

use Lagdo\DbAdmin\Db\Driver\Facades\QueryFacade;

/**
 * Facade to table query functions
 */
trait QueryTrait
{
    use AbstractTrait;

    /**
     * Get the facade
     *
     * @return QueryFacade
     */
    protected function queryFacade(): QueryFacade
    {
        return $this->di()->g(QueryFacade::class);
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
        $this->utils->input->table = $table;
        $this->utils->input->values = $queryOptions;
        return $this->queryFacade()->getInsertData($table, $queryOptions);
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
        $this->utils->input->table = $table;
        $this->utils->input->values = $values;
        return $this->queryFacade()->insertItem($table, $queryOptions, $values);
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
        $this->utils->input->table = $table;
        $this->utils->input->values = $queryOptions;
        return $this->queryFacade()->getUpdateData($table, $queryOptions);
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
        $this->utils->input->table = $table;
        $this->utils->input->values = $values;
        return $this->queryFacade()->updateItem($table, $queryOptions, $values);
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
        $this->utils->input->table = $table;
        $this->utils->input->values = $queryOptions;
        return $this->queryFacade()->deleteItem($table, $queryOptions);
    }
}

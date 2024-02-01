<?php

namespace Lagdo\DbAdmin\Db\Traits;

use Lagdo\DbAdmin\Db\Facades\QueryFacade;

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
        return $this->queryFacade()->getQueryData($table, $queryOptions);
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
        return $this->queryFacade()->insertItem($table, $queryOptions);
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
        return $this->queryFacade()->updateItem($table, $queryOptions);
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
        return $this->queryFacade()->deleteItem($table, $queryOptions);
    }
}

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
        $this->breadcrumbs(true)
            ->item($this->utils->trans->lang('Tables'))
            ->item("<i><b>$table</b></i>")
            ->item($this->utils->trans->lang($action));
        $this->utils->input->table = $table;
        $this->utils->input->values = $queryOptions;
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
        $this->utils->input->table = $table;
        $this->utils->input->values = $queryOptions;
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
        $this->utils->input->table = $table;
        $this->utils->input->values = $queryOptions;
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
        $this->utils->input->table = $table;
        $this->utils->input->values = $queryOptions;
        return $this->queryFacade()->deleteItem($table, $queryOptions);
    }
}

<?php

namespace Lagdo\DbAdmin\Db\Query;

use Lagdo\DbAdmin\Db\AbstractFacade;
use Exception;

/**
 * Facade to table query functions
 */
trait QueryTrait
{
    /**
     * The proxy
     *
     * @var QueryFacade
     */
    protected $tableQueryFacade = null;

    /**
     * @return AbstractFacade
     */
    abstract public function facade(): AbstractFacade;

    /**
     * Connect to a database server
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     *
     * @return void
     */
    abstract public function connect(string $server, string $database = '', string $schema = '');

    /**
     * Set the breadcrumbs items
     *
     * @param array $breadcrumbs
     *
     * @return void
     */
    abstract protected function setBreadcrumbs(array $breadcrumbs);

    /**
     * Get the proxy
     *
     * @return QueryFacade
     */
    protected function tableQuery(): QueryFacade
    {
        if (!$this->tableQueryFacade) {
            $this->tableQueryFacade = new QueryFacade();
            $this->tableQueryFacade->init($this->facade());
        }
        return $this->tableQueryFacade;
    }

    /**
     * Get data for insert/update on a table
     *
     * @param string $server        The selected server
     * @param string $database      The database name
     * @param string $schema        The database schema
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     * @param string $action        The action title
     *
     * @return array
     */
    public function getQueryData(string $server, string $database, string $schema,
        string $table, array $queryOptions = [], string $action = 'New item'): array
    {
        $this->connect($server, $database, $schema);

        $package = $this->facade()->package;
        $this->setBreadcrumbs([$package->getServerName($server), $database,
            $this->trans->lang('Tables'), $table, $this->trans->lang($action)]);

        $this->util->input()->table = $table;
        $this->util->input()->values = $queryOptions;
        return $this->tableQuery()->getQueryData($table, $queryOptions);
    }

    /**
     * Insert a new item in a table
     *
     * @param string $server        The selected server
     * @param string $database      The database name
     * @param string $schema        The database schema
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function insertItem(string $server, string $database, string $schema, string $table, array $queryOptions): array
    {
        $this->connect($server, $database, $schema);

        $this->util->input()->table = $table;
        $this->util->input()->values = $queryOptions;
        return $this->tableQuery()->insertItem($table, $queryOptions);
    }

    /**
     * Update one or more items in a table
     *
     * @param string $server        The selected server
     * @param string $database      The database name
     * @param string $schema        The database schema
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function updateItem(string $server, string $database, string $schema, string $table, array $queryOptions): array
    {
        $this->connect($server, $database, $schema);

        $this->util->input()->table = $table;
        $this->util->input()->values = $queryOptions;
        return $this->tableQuery()->updateItem($table, $queryOptions);
    }

    /**
     * Delete one or more items in a table
     *
     * @param string $server        The selected server
     * @param string $database      The database name
     * @param string $schema        The database schema
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function deleteItem(string $server, string $database, string $schema, string $table, array $queryOptions): array
    {
        $this->connect($server, $database, $schema);

        $this->util->input()->table = $table;
        $this->util->input()->values = $queryOptions;
        return $this->tableQuery()->deleteItem($table, $queryOptions);
    }
}

<?php

namespace Lagdo\DbAdmin\Db\DbAdmin\Query;

use Lagdo\DbAdmin\Db\DbAdmin\AbstractAdmin;
use Exception;

/**
 * Admin table query functions
 */
trait QueryTrait
{
    /**
     * The proxy
     *
     * @var QueryAdmin
     */
    protected $tableQueryAdmin = null;

    /**
     * @return AbstractAdmin
     */
    abstract public function admin(): AbstractAdmin;

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
     * @return QueryAdmin
     */
    protected function tableQuery(): QueryAdmin
    {
        if (!$this->tableQueryAdmin) {
            $this->tableQueryAdmin = new QueryAdmin();
            $this->tableQueryAdmin->init($this->admin());
        }
        return $this->tableQueryAdmin;
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

        $package = $this->admin()->package;
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

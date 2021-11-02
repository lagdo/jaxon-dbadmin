<?php

namespace Lagdo\DbAdmin\DbAdmin;

use Exception;

/**
 * Admin table query functions
 */
trait TableQueryTrait
{
    /**
     * The proxy
     *
     * @var TableQueryAdmin
     */
    protected $tableQueryAdmin = null;

    /**
     * @return AbstractAdmin
     */
    abstract public function admin();

    /**
     * Connect to a database server
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     *
     * @return array
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
     * @return TableQueryAdmin
     */
    protected function tableQuery()
    {
        if (!$this->tableQueryAdmin) {
            $this->tableQueryAdmin = new TableQueryAdmin();
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
        string $table, array $queryOptions = [], string $action = 'New item')
    {
        $this->connect($server, $database, $schema);

        $options = $this->package->getServerOptions($server);
        $this->setBreadcrumbs([$options['name'], $database,
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
    public function insertItem(
        string $server,
        string $database,
        string $schema,
        string $table,
        array $queryOptions
    )
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
    public function updateItem(
        string $server,
        string $database,
        string $schema,
        string $table,
        array $queryOptions
    )
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
    public function deleteItem(
        string $server,
        string $database,
        string $schema,
        string $table,
        array $queryOptions
    )
    {
        $this->connect($server, $database, $schema);

        $this->util->input()->table = $table;
        $this->util->input()->values = $queryOptions;
        return $this->tableQuery()->deleteItem($table, $queryOptions);
    }
}

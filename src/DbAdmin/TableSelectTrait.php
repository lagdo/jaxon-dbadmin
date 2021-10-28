<?php

namespace Lagdo\DbAdmin\DbAdmin;

use Exception;

/**
 * Admin table select functions
 */
trait TableSelectTrait
{
    /**
     * The proxy
     *
     * @var TableAdmin
     */
    protected $tableSelectAdmin = null;

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
     * @return TableSelectAdmin
     */
    protected function tableSelect()
    {
        if (!$this->tableSelectAdmin) {
            $this->tableSelectAdmin = new TableSelectAdmin();
            $this->tableSelectAdmin->init($this->admin());
        }
        return $this->tableSelectAdmin;
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $server        The selected server
     * @param string $database      The database name
     * @param string $schema        The database schema
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function getSelectData(
        string $server,
        string $database,
        string $schema,
        string $table,
        array $queryOptions = []
    )
    {
        $this->connect($server, $database, $schema);

        $options = $this->package->getServerOptions($server);
        $this->setBreadcrumbs([$options['name'], $database,
            $this->trans->lang('Tables'), $table, $this->trans->lang('Select')]);

        $this->util->input->table = $table;
        $this->util->input->values = $queryOptions;
        return $this->tableSelect()->getSelectData($table, $queryOptions);
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $server        The selected server
     * @param string $database      The database name
     * @param string $schema        The database schema
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function execSelect(
        string $server,
        string $database,
        string $schema,
        string $table,
        array $queryOptions = []
    )
    {
        $this->connect($server, $database, $schema);

        $this->util->input->table = $table;
        $this->util->input->values = $queryOptions;
        return $this->tableSelect()->execSelect($table, $queryOptions);
    }
}

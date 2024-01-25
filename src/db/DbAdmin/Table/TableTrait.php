<?php

namespace Lagdo\DbAdmin\Db\DbAdmin\Table;

use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;
use Lagdo\DbAdmin\Db\DbAdmin\AbstractAdmin;
use Exception;

/**
 * Admin table functions
 */
trait TableTrait
{
    /**
     * The proxy
     *
     * @var TableAdmin
     */
    protected $tableAdmin = null;

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
     * @return TableAdmin
     */
    protected function table(): TableAdmin
    {
        if (!$this->tableAdmin) {
            $this->tableAdmin = new TableAdmin();
            $this->tableAdmin->init($this->admin());
        }
        return $this->tableAdmin;
    }

    /**
     * Get details about a table or a view
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     * @param string $table     The table name
     *
     * @return array
     */
    public function getTableInfo(string $server, string $database, string $schema, string $table): array
    {
        $this->connect($server, $database, $schema);

        $package = $this->admin()->package;
        $this->setBreadcrumbs([$package->getServerName($server), $database, $this->trans->lang('Tables'), $table]);

        $this->util->input()->table = $table;
        return $this->table()->getTableInfo($table);
    }

    /**
     * Get details about a table or a view
     *
     * @param string $server The selected server
     * @param string $database The database name
     * @param string $schema The database schema
     * @param string $table The table name
     *
     * @return array
     * @throws Exception
     */
    public function getTableFields(string $server, string $database, string $schema, string $table): array
    {
        $this->connect($server, $database, $schema);
        $this->util->input()->table = $table;
        return $this->table()->getTableFields($table);
    }

    /**
     * Get the indexes of a table
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     * @param string $table     The table name
     *
     * @return array|null
     */
    public function getTableIndexes(string $server, string $database, string $schema, string $table): ?array
    {
        $this->connect($server, $database, $schema);
        $this->util->input()->table = $table;
        return $this->table()->getTableIndexes($table);
    }

    /**
     * Get the foreign keys of a table
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     * @param string $table     The table name
     *
     * @return array|null
     */
    public function getTableForeignKeys(string $server, string $database, string $schema, string $table): ?array
    {
        $this->connect($server, $database, $schema);
        $this->util->input()->table = $table;
        return $this->table()->getTableForeignKeys($table);
    }

    /**
     * Get the triggers of a table
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     * @param string $table     The table name
     *
     * @return array|null
     */
    public function getTableTriggers(string $server, string $database, string $schema, string $table): ?array
    {
        $this->connect($server, $database, $schema);
        $this->util->input()->table = $table;
        return $this->table()->getTableTriggers($table);
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $server The selected server
     * @param string $database The database name
     * @param string $schema The database schema
     * @param string $table The table name
     *
     * @return array
     * @throws Exception
     */
    public function getTableData(string $server, string $database, string $schema, string $table = ''): array
    {
        $this->connect($server, $database, $schema);

        $package = $this->admin()->package;
        $breadcrumbs = [$package->getServerName($server), $database, $this->trans->lang('Tables')];
        if (($table)) {
            $breadcrumbs[] = $table;
            $breadcrumbs[] = $this->trans->lang('Alter table');
        } else {
            $breadcrumbs[] = $this->trans->lang('Create table');
        }
        $this->setBreadcrumbs($breadcrumbs);

        $this->util->input()->table = $table;
        return $this->table()->getTableData($table);
    }

    /**
     * Get fields for a new column
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     *
     * @return TableFieldEntity
     */
    public function getTableField(string $server, string $database, string $schema): TableFieldEntity
    {
        $this->connect($server, $database, $schema);

        return $this->table()->getTableField();
    }

    /**
     * Create a table
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     * @param array  $values    The table values
     *
     * @return array|null
     */
    public function createTable(string $server, string $database, string $schema, array $values): ?array
    {
        $this->connect($server, $database, $schema);

        $this->util->input()->table = $values['name'];
        $this->util->input()->values = $values;
        return $this->table()->createTable($values);
    }

    /**
     * Alter a table
     *
     * @param string $server The selected server
     * @param string $database The database name
     * @param string $schema The database schema
     * @param string $table The table name
     * @param array $values The table values
     *
     * @return array|null
     * @throws Exception
     */
    public function alterTable(string $server, string $database, string $schema, string $table, array $values): ?array
    {
        $this->connect($server, $database, $schema);
        $this->util->input()->table = $table;
        $this->util->input()->values = $values;
        return $this->table()->alterTable($table, $values);
    }

    /**
     * Drop a table
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     * @param string $table     The table name
     *
     * @return array|null
     */
    public function dropTable(string $server, string $database, string $schema, string $table): ?array
    {
        $this->connect($server, $database, $schema);
        $this->util->input()->table = $table;
        return $this->table()->dropTable($table);
    }
}

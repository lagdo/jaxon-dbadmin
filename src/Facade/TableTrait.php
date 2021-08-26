<?php

namespace Lagdo\Adminer\Facade;

use Exception;

/**
 * Facade to calls to table functions
 */
trait TableTrait
{
    /**
     * The proxy
     *
     * @var TableFacade
     */
    protected $tableFacade = null;

    /**
     * Get the proxy
     *
     * @return TableFacade
     */
    protected function table()
    {
        if (!$this->tableFacade) {
            $this->tableFacade = new TableFacade();
            $this->tableFacade->init($this);
        }
        return $this->tableFacade;
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
    public function getTableInfo(string $server, string $database, string $schema, string $table)
    {
        $options = $this->connect($server, $database, $schema);

        $this->setBreadcrumbs([$options['name'], $database, $this->util->lang('Tables'), $table]);

        $this->util->input->table = $table;
        return $this->table()->getTableInfo($table);
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
    public function getTableFields(string $server, string $database, string $schema, string $table)
    {
        $this->connect($server, $database, $schema);
        $this->util->input->table = $table;
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
    public function getTableIndexes(string $server, string $database, string $schema, string $table)
    {
        $this->connect($server, $database, $schema);
        $this->util->input->table = $table;
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
    public function getTableForeignKeys(string $server, string $database, string $schema, string $table)
    {
        $this->connect($server, $database, $schema);
        $this->util->input->table = $table;
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
    public function getTableTriggers(string $server, string $database, string $schema, string $table)
    {
        $this->connect($server, $database, $schema);
        $this->util->input->table = $table;
        return $this->table()->getTableTriggers($table);
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     * @param string $table     The table name
     *
     * @return array
     */
    public function getTableData(string $server, string $database, string $schema, string $table = '')
    {
        $options = $this->connect($server, $database, $schema);

        $breadcrumbs = [$options['name'], $database, $this->util->lang('Tables')];
        if (($table)) {
            $breadcrumbs[] = $table;
            $breadcrumbs[] = $this->util->lang('Alter table');
        } else {
            $breadcrumbs[] = $this->util->lang('Create table');
        }
        $this->setBreadcrumbs($breadcrumbs);

        $this->util->input->table = $table;
        return $this->table()->getTableData($table);
    }

    /**
     * Get fields for a new column
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     *
     * @return array
     */
    public function getTableField(string $server, string $database, string $schema)
    {
        $options = $this->connect($server, $database, $schema);
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
    public function createTable(string $server, string $database, string $schema, array $values)
    {
        $this->connect($server, $database, $schema);
        $this->util->input->table = $table;
        $this->util->input->values = $values;
        return $this->table()->createTable($values);
    }

    /**
     * Alter a table
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     * @param string $table     The table name
     * @param array  $values    The table values
     *
     * @return array|null
     */
    public function alterTable(string $server, string $database, string $schema, string $table, array $values)
    {
        $this->connect($server, $database, $schema);
        $this->util->input->table = $table;
        $this->util->input->values = $values;
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
    public function dropTable(string $server, string $database, string $schema, string $table)
    {
        $this->connect($server, $database, $schema);
        $this->util->input->table = $table;
        return $this->table()->dropTable($table);
    }
}
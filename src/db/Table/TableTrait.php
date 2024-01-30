<?php

namespace Lagdo\DbAdmin\Db\Table;

use Exception;
use Jaxon\Di\Container;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

/**
 * Facade to table functions
 */
trait TableTrait
{
    /**
     * @return Container
     */
    abstract public function di(): Container;

    /**
     * Connect to a database server
     *
     * @return void
     */
    abstract public function connectToSchema();

    /**
     * Set the breadcrumbs items
     *
     * @param bool $showDatabase
     * @param array $breadcrumbs
     *
     * @return void
     */
    abstract protected function setBreadcrumbs(bool $showDatabase = false, array $breadcrumbs = []);

    /**
     * Get the proxy
     *
     * @return TableFacade
     */
    protected function table(): TableFacade
    {
        return $this->di()->g(TableFacade::class);
    }

    /**
     * Get details about a table or a view
     *
     * @param string $table     The table name
     *
     * @return array
     */
    public function getTableInfo(string $table): array
    {
        $this->connectToSchema();

        $this->setBreadcrumbs(true, [$this->trans->lang('Tables'), $table]);

        $this->util->input()->table = $table;
        return $this->table()->getTableInfo($table);
    }

    /**
     * Get details about a table or a view
     *
     * @param string $table The table name
     *
     * @return array
     * @throws Exception
     */
    public function getTableFields(string $table): array
    {
        $this->connectToSchema();
        $this->util->input()->table = $table;
        return $this->table()->getTableFields($table);
    }

    /**
     * Get the indexes of a table
     *
     * @param string $table     The table name
     *
     * @return array|null
     */
    public function getTableIndexes(string $table): ?array
    {
        $this->connectToSchema();
        $this->util->input()->table = $table;
        return $this->table()->getTableIndexes($table);
    }

    /**
     * Get the foreign keys of a table
     *
     * @param string $table     The table name
     *
     * @return array|null
     */
    public function getTableForeignKeys(string $table): ?array
    {
        $this->connectToSchema();
        $this->util->input()->table = $table;
        return $this->table()->getTableForeignKeys($table);
    }

    /**
     * Get the triggers of a table
     *
     * @param string $table     The table name
     *
     * @return array|null
     */
    public function getTableTriggers(string $table): ?array
    {
        $this->connectToSchema();
        $this->util->input()->table = $table;
        return $this->table()->getTableTriggers($table);
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     *
     * @return array
     * @throws Exception
     */
    public function getTableData(string $table = ''): array
    {
        $this->connectToSchema();

        $breadcrumbs = [$this->trans->lang('Tables')];
        if (($table)) {
            $breadcrumbs[] = $table;
            $breadcrumbs[] = $this->trans->lang('Alter table');
        } else {
            $breadcrumbs[] = $this->trans->lang('Create table');
        }
        $this->setBreadcrumbs(true, $breadcrumbs);

        $this->util->input()->table = $table;
        return $this->table()->getTableData($table);
    }

    /**
     * Get fields for a new column
     *
     * @return TableFieldEntity
     */
    public function getTableField(): TableFieldEntity
    {
        $this->connectToSchema();

        return $this->table()->getTableField();
    }

    /**
     * Create a table
     *
     * @param array  $values    The table values
     *
     * @return array|null
     */
    public function createTable(array $values): ?array
    {
        $this->connectToSchema();

        $this->util->input()->table = $values['name'];
        $this->util->input()->values = $values;
        return $this->table()->createTable($values);
    }

    /**
     * Alter a table
     *
     * @param string $table The table name
     * @param array $values The table values
     *
     * @return array|null
     * @throws Exception
     */
    public function alterTable(string $table, array $values): ?array
    {
        $this->connectToSchema();
        $this->util->input()->table = $table;
        $this->util->input()->values = $values;
        return $this->table()->alterTable($table, $values);
    }

    /**
     * Drop a table
     *
     * @param string $table     The table name
     *
     * @return array|null
     */
    public function dropTable(string $table): ?array
    {
        $this->connectToSchema();
        $this->util->input()->table = $table;
        return $this->table()->dropTable($table);
    }
}

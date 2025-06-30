<?php

namespace Lagdo\DbAdmin\Db\Traits;

use Exception;
use Lagdo\DbAdmin\Db\Facades\TableFacade;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

/**
 * Facade to table functions
 */
trait TableTrait
{
    use AbstractTrait;

    /**
     * Get the facade
     *
     * @return TableFacade
     */
    protected function tableFacade(): TableFacade
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
        $this->bcdb()->breadcrumb($this->utils->trans->lang('Tables'))->breadcrumb($table);
        $this->utils->input->table = $table;
        return $this->tableFacade()->getTableInfo($table);
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
        $this->utils->input->table = $table;
        return $this->tableFacade()->getTableFields($table);
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
        $this->utils->input->table = $table;
        return $this->tableFacade()->getTableIndexes($table);
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
        $this->utils->input->table = $table;
        return $this->tableFacade()->getTableForeignKeys($table);
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
        $this->utils->input->table = $table;
        return $this->tableFacade()->getTableTriggers($table);
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
        $this->bcdb()->breadcrumb($this->utils->trans->lang('Tables'));
        if (!$table) {
            $this->breadcrumb($this->utils->trans->lang('Create table'));
        } else {
            $this->breadcrumb($table)->breadcrumb($this->utils->trans->lang('Alter table'));
        }
        $this->utils->input->table = $table;
        return $this->tableFacade()->getTableData($table);
    }

    /**
     * Get fields for a new column
     *
     * @return TableFieldEntity
     */
    public function getTableField(): TableFieldEntity
    {
        $this->connectToSchema();
        return $this->tableFacade()->getTableField();
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
        $this->utils->input->table = $values['name'];
        $this->utils->input->values = $values;
        return $this->tableFacade()->createTable($values);
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
        $this->utils->input->table = $table;
        $this->utils->input->values = $values;
        return $this->tableFacade()->alterTable($table, $values);
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
        $this->utils->input->table = $table;
        return $this->tableFacade()->dropTable($table);
    }
}

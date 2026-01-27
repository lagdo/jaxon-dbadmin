<?php

namespace Lagdo\DbAdmin\Db\Driver\Facades;

use Lagdo\DbAdmin\Db\UiData\Ddl\ColumnInputDto;
use Lagdo\DbAdmin\Driver\Dto\TableFieldDto;
use Exception;

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
        $this->breadcrumbs(true)
            ->item($this->utils->trans->lang('Tables'))
            ->item("<i><b>$table</b></i>");
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
    public function getTableMetadata(string $table = ''): array
    {
        $this->connectToSchema();
        $this->breadcrumbs(true)->item($this->utils->trans->lang('Tables'));
        if (!$table) {
            $this->breadcrumbs()->item($this->utils->trans->lang('Create table'));
        } else {
            $this->breadcrumbs()->item("<i><b>$table</b></i>")
                ->item($this->utils->trans->lang('Alter table'));
        }
        $this->utils->input->table = $table;
        return $this->tableFacade()->getTableMetadata($table);
    }

    /**
     * Get fields for a new column
     *
     * @return TableFieldDto
     */
    public function getTableField(): TableFieldDto
    {
        $this->connectToSchema();
        return $this->tableFacade()->getTableField();
    }

    /**
     * Get field types
     *
     * @param string $type  The type name
     *
     * @return array
     */
    public function getFieldTypes(string $type = ''): array
    {
        // Must be called after the connection to a schema is made.
        // $this->connectToSchema();
        return $this->tableFacade()->getFieldTypes($type);
    }

    /**
     * Get SQL commands to create a table
     *
     * @param array $options     The table options
     * @param array<ColumnInputDto> $columns
     *
     * @return array
     */
    public function getCreateTableQueries(array $options, array $columns): array
    {
        $this->connectToSchema();
        return $this->tableFacade()->getCreateTableQueries($options, $columns);
    }

    /**
     * Create a table
     *
     * @param array $options     The table options
     * @param array<ColumnInputDto> $columns
     *
     * @return array|null
     */
    public function createTable(array $options, array $columns): ?array
    {
        $this->connectToSchema();
        return $this->tableFacade()->createTable($options, $columns);
    }

    /**
     * Get SQL command to alter a table
     *
     * @param string $name       The table name
     * @param array $options     The table options
     * @param array<ColumnInputDto> $columns
     *
     * @return array
     */
    public function getAlterTableQueries(string $name, array $options, array $columns): array
    {
        $this->connectToSchema();
        return $this->tableFacade()->getAlterTableQueries($name, $options, $columns);
    }

    /**
     * Alter a table
     *
     * @param string $name       The table name
     * @param array $options     The table options
     * @param array<ColumnInputDto> $columns
     *
     * @return array|null
     * @throws Exception
     */
    public function alterTable(string $name, array $options, array $columns): ?array
    {
        $this->connectToSchema();
        return $this->tableFacade()->alterTable($name, $options, $columns);
    }

    /**
     * Drop a table
     *
     * @param string $table     The table name
     *
     * @return array
     */
    public function dropTable(string $table): array
    {
        $this->connectToSchema();
        return $this->tableFacade()->dropTable($table);
    }
}

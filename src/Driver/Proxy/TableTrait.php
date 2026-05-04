<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\DdInputDto;
use Exception;

/**
 * Proxy to table functions
 */
trait TableTrait
{
    use AbstractProxyTrait;

    /**
     * Get the proxy
     *
     * @return TableProxy
     */
    protected function tableProxy(): TableProxy
    {
        return $this->di()->g(TableProxy::class);
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
            ->item($this->utils()->lang('Tables'))
            ->item("<i><b>$table</b></i>");
        $this->utils()->input->table = $table;
        return $this->tableProxy()->getTableInfo($table);
    }

    /**
     * Get details about a table or a view
     *
     * @param string $table The table name
     *
     * @return array
     * @throws Exception
     */
    public function getTableColumns(string $table): array
    {
        $this->connectToSchema();
        $this->utils()->input->table = $table;
        return $this->tableProxy()->getTableColumns($table);
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
        $this->utils()->input->table = $table;
        return $this->tableProxy()->getTableIndexes($table);
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
        $this->utils()->input->table = $table;
        return $this->tableProxy()->getTableForeignKeys($table);
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
        $this->utils()->input->table = $table;
        return $this->tableProxy()->getTableTriggers($table);
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
        $this->breadcrumbs(true)->item($this->utils()->lang('Tables'));
        if (!$table) {
            $this->breadcrumbs()->item($this->utils()->lang('Create table'));
        } else {
            $this->breadcrumbs()->item("<i><b>$table</b></i>")
                ->item($this->utils()->lang('Alter table'));
        }
        $this->utils()->input->table = $table;
        return $this->tableProxy()->getTableMetadata($table);
    }

    /**
     * Get a new column
     *
     * @return DdInputDto
     */
    public function newColumnInput(): DdInputDto
    {
        $this->connectToSchema();
        return $this->tableProxy()->newColumnInput();
    }

    /**
     * @param DdInputDto $input
     *
     * @return DdInputDto
     */
    public function setInputFieldProperties(DdInputDto $input): DdInputDto
    {
        $this->connectToSchema();
        return $this->tableProxy()->setInputFieldProperties($input);
    }

    /**
     * Get SQL commands to create a table
     *
     * @param array $options     The table options
     * @param array<DdInputDto> $inputs
     *
     * @return array
     */
    public function getCreateTableQueries(array $options, array $inputs): array
    {
        $this->connectToSchema();
        return $this->tableProxy()->getCreateTableQueries($options, $inputs);
    }

    /**
     * Create a table
     *
     * @param array $options     The table options
     * @param array<DdInputDto> $inputs
     *
     * @return array|null
     */
    public function createTable(array $options, array $inputs): ?array
    {
        $this->connectToSchema();
        return $this->tableProxy()->createTable($options, $inputs);
    }

    /**
     * Get SQL command to alter a table
     *
     * @param string $name       The table name
     * @param array $options     The table options
     * @param array<DdInputDto> $inputs
     *
     * @return array
     */
    public function getAlterTableQueries(string $name, array $options, array $inputs): array
    {
        $this->connectToSchema();
        return $this->tableProxy()->getAlterTableQueries($name, $options, $inputs);
    }

    /**
     * Alter a table
     *
     * @param string $name       The table name
     * @param array $options     The table options
     * @param array<DdInputDto> $inputs
     *
     * @return array|null
     * @throws Exception
     */
    public function alterTable(string $name, array $options, array $inputs): ?array
    {
        $this->connectToSchema();
        return $this->tableProxy()->alterTable($name, $options, $inputs);
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
        return $this->tableProxy()->dropTable($table);
    }
}

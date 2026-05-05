<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Driver\Sql\Dto\TableAlterDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableCreateDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\ColumnDdDto;
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
     * @param string $table
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
     * @param string $table
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
     * @param string $table
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
     * @param string $table
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
     * @param string $table
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
     * @param string $table
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
     * @return ColumnDdDto
     */
    public function newColumnInput(): ColumnDdDto
    {
        $this->connectToSchema();
        return $this->tableProxy()->newColumnInput();
    }

    /**
     * @param ColumnDdDto $input
     *
     * @return ColumnDdDto
     */
    public function setInputFieldProperties(ColumnDdDto $input): ColumnDdDto
    {
        $this->connectToSchema();
        return $this->tableProxy()->setInputFieldProperties($input);
    }

    /**
     * @param array $tableInputs
     *
     * @return TableCreateDto
     */
    public function getCreateTableDto(array $tableInputs): TableCreateDto
    {
        return $this->tableProxy()->getCreateTableDto($tableInputs);
    }

    /**
     * Get SQL commands to create a table
     *
     * @param array $tableInputs
     * @param array<ColumnDdDto> $columnsInputs
     *
     * @return array
     */
    public function getCreateTableQueries(array $tableInputs, array $columnsInputs): array
    {
        $this->connectToSchema();
        return $this->tableProxy()->getCreateTableQueries($tableInputs, $columnsInputs);
    }

    /**
     * Create a table
     *
     * @param array $tableInputs
     * @param array<ColumnDdDto> $columnsInputs
     *
     * @return array|null
     */
    public function createTable(array $tableInputs, array $columnsInputs): ?array
    {
        $this->connectToSchema();
        return $this->tableProxy()->createTable($tableInputs, $columnsInputs);
    }

    /**
     * @param array $tableInputs
     *
     * @return TableAlterDto
     */
    public function getAlterTableDto(array $tableInputs): TableAlterDto
    {
        return $this->tableProxy()->getAlterTableDto($tableInputs);
    }

    /**
     * Get SQL command to alter a table
     *
     * @param string $table
     * @param array $tableInputs
     * @param array<ColumnDdDto> $columnsInputs
     *
     * @return array
     */
    public function getAlterTableQueries(string $table, array $tableInputs, array $columnsInputs): array
    {
        $this->connectToSchema();
        return $this->tableProxy()->getAlterTableQueries($table, $tableInputs, $columnsInputs);
    }

    /**
     * Alter a table
     *
     * @param string $table
     * @param array $tableInputs
     * @param array<ColumnDdDto> $columnsInputs
     *
     * @return array|null
     * @throws Exception
     */
    public function alterTable(string $table, array $tableInputs, array $columnsInputs): ?array
    {
        $this->connectToSchema();
        return $this->tableProxy()->alterTable($table, $tableInputs, $columnsInputs);
    }

    /**
     * Drop a table
     *
     * @param string $table
     *
     * @return array
     */
    public function dropTable(string $table): array
    {
        $this->connectToSchema();
        return $this->tableProxy()->dropTable($table);
    }
}

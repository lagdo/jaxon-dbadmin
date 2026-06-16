<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\ColumnFormDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\TableFormDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectDqDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\ExecResultDto;
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
     * Get the proxy
     *
     * @return SelectProxy
     */
    protected function selectProxy(): SelectProxy
    {
        return $this->di()->g(SelectProxy::class);
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
        $this->utils()->setInputTable($table);
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
        $this->utils()->setInputTable($table);
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
        $this->utils()->setInputTable($table);
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
        $this->utils()->setInputTable($table);
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
        $this->utils()->setInputTable($table);
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
        $this->utils()->setInputTable($table);
        return $this->tableProxy()->getTableMetadata($table);
    }

    /**
     * Get a new column
     *
     * @param array|null $values
     *
     * @return ColumnFormDto
     */
    public function newColumnInput(array|null $values = null): ColumnFormDto
    {
        $this->connectToSchema();
        return $this->tableProxy()->newColumnInput($values);
    }

    /**
     * @param ColumnFormDto $input
     *
     * @return ColumnFormDto
     */
    public function setInputFieldProperties(ColumnFormDto $input): ColumnFormDto
    {
        $this->connectToSchema();
        return $this->tableProxy()->setInputFieldProperties($input);
    }

    /**
     * Get SQL commands to create a table
     *
     * @param TableFormDto $table
     *
     * @return array
     */
    public function getCreateTableQueries(TableFormDto $table): array
    {
        $this->connectToSchema();
        return $this->tableProxy()->getCreateTableQueries($table);
    }

    /**
     * Create a table
     *
     * @param TableFormDto $table
     *
     * @return ExecResultDto
     */
    public function createTable(TableFormDto $table): ExecResultDto
    {
        $this->connectToSchema();
        return $this->tableProxy()->createTable($table);
    }

    /**
     * Get SQL command to alter a table
     *
     * @param TableFormDto $table
     *
     * @return array
     */
    public function getAlterTableQueries(TableFormDto $table): array
    {
        $this->connectToSchema();
        return $this->tableProxy()->getAlterTableQueries($table);
    }

    /**
     * Alter a table
     *
     * @param TableFormDto $table
     *
     * @return ExecResultDto
     */
    public function alterTable(TableFormDto $table): ExecResultDto
    {
        $this->connectToSchema();
        return $this->tableProxy()->alterTable($table);
    }

    /**
     * Get SQL command to drop a table
     *
     * @param string $table
     *
     * @return array
     */
    public function getDropTableQueries(string $table): array
    {
        $this->connectToSchema();
        return $this->tableProxy()->getDropTableQueries($table);
    }

    /**
     * Drop a table
     *
     * @param string $table
     *
     * @return ExecResultDto
     */
    public function dropTable(string $table): ExecResultDto
    {
        $this->connectToSchema();
        return $this->tableProxy()->dropTable($table);
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     * @param array $queryParams The user params
     *
     * @return SelectDqDto
     * @throws Exception
     */
    public function getSelectParams(string $table, array $queryParams = []): SelectDqDto
    {
        $this->connectToSchema();
        $this->utils()->setInputTable($table);
        $this->utils()->setInputValues($queryParams);
        return $this->selectProxy()->getSelectParams($table, $queryParams);
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     * @param array $queryParams The user params
     *
     * @return int
     * @throws Exception
     */
    public function countSelect(string $table, array $queryParams = []): int
    {
        $this->connectToSchema();
        $this->utils()->setInputTable($table);
        $this->utils()->setInputValues($queryParams);
        return $this->selectProxy()->countSelect($table, $queryParams);
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     * @param array $queryParams The user params
     *
     * @return ExecResultDto
     * @throws Exception
     */
    public function execSelect(string $table, array $queryParams = []): ExecResultDto
    {
        $this->connectToSchema();
        $this->utils()->setInputTable($table);
        $this->utils()->setInputValues($queryParams);
        return $this->selectProxy()->execSelect($table, $queryParams);
    }
}

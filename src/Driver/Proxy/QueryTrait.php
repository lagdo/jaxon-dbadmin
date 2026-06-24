<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Jaxon\Request\Upload\FileInterface;
use Lagdo\DbAdmin\Support\Driver\UiDto\ExecOptions;
use Lagdo\DbAdmin\Support\Driver\UiDto\ExecResultDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\QueryListDto;

/**
 * Proxy to table query functions
 */
trait QueryTrait
{
    use AbstractProxyTrait;

    /**
     * Get the proxy
     *
     * @return QueryProxy
     */
    protected function queryProxy(): QueryProxy
    {
        return $this->di()->g(QueryProxy::class);
    }

    /**
     * Get the proxy
     *
     * @return ExportProxy
     */
    protected function exportProxy(): ExportProxy
    {
        return $this->di()->g(ExportProxy::class);
    }

    /**
     * Get data for insert on a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function getInsertData(string $table, array $queryOptions = []): array
    {
        $this->connectToSchema();
        return $this->queryProxy()->getInsertData($table, $queryOptions);
    }

    /**
     * Build the SQL query to insert a new item in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     * @param array  $values        The updated values
     *
     * @return QueryListDto
     */
    public function getInsertRowQuery(string $table, array $queryOptions, array $values): QueryListDto
    {
        $this->connectToSchema();
        return $this->queryProxy()->getInsertRowQuery($table, $queryOptions, $values);
    }

    /**
     * Insert a new item in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     * @param array  $values        The updated values
     *
     * @return ExecResultDto
     */
    public function insertItem(string $table, array $queryOptions, array $values): ExecResultDto
    {
        $this->connectToSchema();
        return $this->queryProxy()->insertItem($table, $queryOptions, $values);
    }

    /**
     * Get data for update/delete in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function getRowForUpdate(string $table, array $queryOptions = []): array
    {
        $this->connectToSchema();
        return $this->queryProxy()->getRowForUpdate($table, $queryOptions);
    }

    /**
     * Build the SQL query to update one or more items in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     * @param array  $values        The updated values
     *
     * @return QueryListDto
     */
    public function getUpdateRowQuery(string $table, array $queryOptions, array $values): QueryListDto
    {
        $this->connectToSchema();
        return $this->queryProxy()->getUpdateRowQuery($table, $queryOptions, $values);
    }

    /**
     * Update one or more items in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     * @param array  $values        The updated values
     *
     * @return ExecResultDto
     */
    public function updateRow(string $table, array $queryOptions, array $values): ExecResultDto
    {
        $this->connectToSchema();
        return $this->queryProxy()->updateRow($table, $queryOptions, $values);
    }

    /**
     * Update one or more items in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     * @param array  $values        The updated values
     *
     * @return array
     */
    public function getUpdatedRow(string $table, array $queryOptions, array $values): array
    {
        return $this->queryProxy()->getUpdatedRow($table, $queryOptions, $values);
    }

    /**
     * Build the SQL query to delete one or more items in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return QueryListDto
     */
    public function getDeleteRowQuery(string $table, array $queryOptions): QueryListDto
    {
        $this->connectToSchema();
        return $this->queryProxy()->getDeleteRowQuery($table, $queryOptions);
    }

    /**
     * Delete one or more items in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return ExecResultDto
     */
    public function deleteRow(string $table, array $queryOptions): ExecResultDto
    {
        $this->connectToSchema();
        return $this->queryProxy()->deleteRow($table, $queryOptions);
    }

    /**
     * Prepare a query
     *
     * @return void
     */
    public function prepareQueryExec()
    {
        $this->breadcrumbs(true)->item($this->utils()->lang('Query'));
    }

    /**
     * Execute the queries in a string
     *
     * @param string $queryText       The queries to execute
     * @param ExecOptions $options
     *
     * @return ExecResultDto
     */
    public function executeQueriesInText(string $queryText, ExecOptions $options): ExecResultDto
    {
        $this->connectToSchema();
        return $this->queryProxy()->executeQueriesInText($queryText, $options);
    }

    /**
     * Get data for import
     *
     * @return array
     */
    public function getImportOptions(): array
    {
        $this->connectToDatabase();
        $this->breadcrumbs(true)->item($this->utils()->lang('Import'));
        return $this->queryProxy()->getImportOptions();
    }

    /**
     * Execute the queries in an uploaded file
     *
     * @param FileInterface $file The uploaded file
     * @param ExecOptions $options
     *
     * @return ExecResultDto
     */
    public function executeQueriesInFile(FileInterface $file, ExecOptions $options): ExecResultDto
    {
        $this->connectToSchema();
        return $this->queryProxy()->executeQueriesInFile($file, $options);
    }

    /**
     * Get data for export
     *
     * @return array
     */
    public function getExportOptions(): array
    {
        $this->connectToDatabase();
        $this->breadcrumbs(true)->item($this->utils()->lang('Export'));
        return $this->exportProxy()->getExportOptions();
    }

    /**
     * @return array
     */
    public function getExportSelection(): array
    {
        $this->connectToServer();
        return $this->exportProxy()->getExportSelection();
    }

    /**
     * Export databases
     * The databases and tables parameters are array where the keys are names and the values
     * are boolean which indicate whether the corresponding data should be exported too.
     *
     * @param array  $databases     The databases to dump
     * @param array  $dumpOptions   The export options
     *
     * @return array|string
     */
    public function exportDatabases(array $databases, array $dumpOptions)
    {
        $this->connectToServer();
        return $this->exportProxy()->exportDatabases($databases, $dumpOptions);
    }
}

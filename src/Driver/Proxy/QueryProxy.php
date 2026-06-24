<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Jaxon\Request\Upload\FileInterface;
use Lagdo\DbAdmin\Driver\Sql\Connection\QueryResultInterface;
use Lagdo\DbAdmin\Driver\Sql\Dto\QueryClauseDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\QueryStreamDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\SelectDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\ExecOptions;
use Lagdo\DbAdmin\Support\Driver\UiDto\ExecResultDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dml\RowDataReader;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dml\RowDataWriter;
use Lagdo\DbAdmin\Support\Driver\UiDto\QueryListDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\TableImport;

use function array_keys;
use function count;
use function explode;
use function fgets;

/**
 * Proxy to table query functions
 */
class QueryProxy extends AbstractDriverProxy
{
    /**
     * @var QueryProcessor
     */
    private QueryProcessor $processor;

    /**
     * @var RowDataReader
     */
    private RowDataReader $reader;

    /**
     * @var RowDataWriter
     */
    private RowDataWriter $writer;

    /**
     * @var TableImport
     */
    private TableImport $import;

    /**
     * @param QueryProcessor $processor
     *
     * @return static
     */
    public function setProcessor(QueryProcessor $processor): static
    {
        $this->processor = $processor;
        return $this;
    }

    /**
     * @param string $action
     * read => edit action for single row insert, update or delete
     * save => save action for insert, update or delete
     * select => edit action for bulk update
     * clone => clone a selected set of data rows
     * @param string $operation
     *
     * @return RowDataWriter
     */
    private function writer(string $action, string $operation): RowDataWriter
    {
        $this->writer ??= (new RowDataWriter($this))->init();
        return $this->writer->action($action, $operation);
    }

    /**
     * @param string $action
     * @param string $operation
     *
     * @return RowDataReader
     */
    private function reader(string $action, string $operation): RowDataReader
    {
        $this->reader ??= new RowDataReader($this);
        return $this->reader->action($action, $operation);
    }

    /**
     * @return TableImport
     */
    private function import(): TableImport
    {
        return $this->import ??= new TableImport($this);
    }

    /**
     * Get data for insert in a table
     *
     * @param string $table         The table name
     * @param array  $options       The query options
     *
     * @return array
     */
    public function getInsertData(string $table, array $options = []): array
    {
        $action = 'read';
        $operation = 'insert';

        [$columns,] = $this->reader($action, $operation)->getTableColumns($table, $options);
        if (empty($columns)) {
            return [
                'error' => $this->utils()->lang('You have no privileges to update this table.'),
            ];
        }

        // No data when inserting a new row
        return [
            'columns' => $this->writer($action, $operation)->getInputValues($columns, $options),
        ];
    }

    /**
     * Get data for update/delete of a single row.
     *
     * @param string $table         The table name
     * @param array  $options       The query options
     *
     * @return array
     */
    public function getRowForUpdate(string $table, array $options = []): array
    {
        $action = 'read';
        $operation = 'update';

        // From edit.inc.php
        [$columns, $where] = $this->reader($action, $operation)->getTableColumns($table, $options);
        if (empty($columns) || !$where) {
            return [
                'error' => $this->utils()->lang('You have no privileges to update this table.'),
            ];
        }

        $select = $this->reader($action, $operation)->getSelectClauses($columns);
        if (count($select) === 0) {
            return [
                'error' => $this->utils()->lang('Unable to find the data row.'),
            ]; // No data
        }

        $result = $this->findRow($table, $select, [$where]);
        if ($result->hasError() || !($row = $result->fetchAssoc())) {
            return [
                'error' => $this->utils()->lang('Unable to find the data row.'),
            ]; // Error
        }

        return [
            'columns' => $this->writer($action, $operation)->getInputValues($columns, $row),
        ];
    }

    /**
     * Build the SQL query to insert a new item in a table
     *
     * @param string $table         The table name
     * @param array  $options       The query options
     * @param array  $values        The updated values
     *
     * @return QueryListDto
     */
    public function getInsertRowQuery(string $table, array $options, array $values): QueryListDto
    {
        $action = 'save';
        $operation = 'insert';

        [$columns,] = $this->reader($action, $operation)->getTableColumns($table, $options);
        $values = $this->reader($action, $operation)->getInputValues($columns, $values);
        $query = $this->statement()->getInsertRowQuery($table, $values);

        return $query !== '' ? new QueryListDto(queries: [$query]) :
            new QueryListDto(error: $this->utils()
                ->lang('Unable to build the SQL code for this insert query.'));
    }

    /**
     * Insert a new item in a table
     *
     * @param string $table         The table name
     * @param array  $options       The query options
     * @param array  $values        The updated values
     *
     * @return ExecResultDto
     */
    public function insertItem(string $table, array $options, array $values): ExecResultDto
    {
        $execOptions = new ExecOptions(true, true);
        $execOptions->setExecOptions(true, true, false);
        $execOptions->withLogger = true;

        $insert = $this->getInsertRowQuery($table, $options, $values);
        $result = $this->processor->executeQueryList($insert, $execOptions);
        if ($result->error === null) {
            $lastId = $this->engine()->lastAutoIncrementId();
            $result->message = $this->utils()->lang('The item%s was inserted.',
                $lastId ? " $lastId" : '');
        }

        return $result;
    }

    /**
     * Build the SQL query to update one or more items in a table
     *
     * @param string $table         The table name
     * @param array  $options       The query options
     * @param array  $values        The updated values
     *
     * @return QueryListDto
     */
    public function getUpdateRowQuery(string $table, array $options, array $values): QueryListDto
    {
        $action = 'save';
        $operation = 'update';

        [$columns, $where] = $this->reader($action, $operation)->getTableColumns($table, $options);
        $values = $this->reader($action, $operation)->getInputValues($columns, $values);
        $limit = $this->reader($action, $operation)->getQueryLimit($table, $options);

        $query = $this->statement()->getUpdateRowQuery($table, $values, "\nWHERE $where", $limit);
        return $query !== '' ? new QueryListDto(queries: [$query]) :
            new QueryListDto(error: $this->utils()
                ->lang('Unable to build the SQL code for this insert query.'));
    }

    /**
     * Update one or more items in a table
     *
     * @param string $table         The table name
     * @param array  $options       The query options
     * @param array  $values        The updated values
     *
     * @return ExecResultDto
     */
    public function updateRow(string $table, array $options, array $values): ExecResultDto
    {
        $execOptions = new ExecOptions(true, true);
        $execOptions->setExecOptions(true, true, false);
        $execOptions->withLogger = true;

        $update = $this->getUpdateRowQuery($table, $options, $values);
        $result = $this->processor->executeQueryList($update, $execOptions);
        if ($result->error === null) {
            $result->message = $this->utils()->lang('The item was updated.');
        }

        return $result;
    }

    /**
     * @param string $table
     * @param array $columns
     * @param array $filters
     *
     * @return QueryResultInterface
     */
    private function findRow(string $table, array $columns, array $filters): QueryResultInterface
    {
        $tableName = [$this->statement()->escapeTableName($table)];
        $clauses = [
            new QueryClauseDto('SELECT', ', ', $columns),
            new QueryClauseDto('FROM', ', ', $tableName),
            new QueryClauseDto('WHERE', ' AND ', $filters),
        ];

        $select = new SelectDto($clauses, limit: 1, offset: 0);
        $query = $this->statement()->getTableSelectQuery($select);

        return $this->engine()->executeQuery($query);
    }

    /**
     * Update one or more items in a table
     *
     * @param string $table         The table name
     * @param array  $options       The query options
     * @param array  $values        The updated values
     *
     * @return array
     */
    public function getUpdatedRow(string $table, array $options, array $values): array
    {
        $action = 'save';
        $operation = 'update';

        [$columns, $where] = $this->reader($action, $operation)->getTableColumns($table, $options);
        $values = $this->reader($action, $operation)->getInputValues($columns, $values);

        // Get the modified data
        // Todo: check if the values in the where clause are changed.
        $result = $this->findRow($table, array_keys($values), [$where]);
        if ($result->hasError() || !($row = $result->fetchAssoc())) {
            return [
                'warning' => $this->utils()->lang('Unable to read the updated row.'),
            ];
        }

        return [
            'cols' => $this->writer($action, $operation)->getUpdatedRow($row, $columns, $options),
        ];
    }

    /**
     * Build the SQL query to delete one or more items in a table
     *
     * @param string $table         The table name
     * @param array  $options       The query options
     *
     * @return QueryListDto
     */
    public function getDeleteRowQuery(string $table, array $options): QueryListDto
    {
        $action = 'save';
        $operation = 'update';

        [, $where] = $this->reader($action, $operation)->getTableColumns($table, $options);
        $limit = $this->reader($action, $operation)->getQueryLimit($table, $options);

        $query = $this->statement()->getDeleteRowQuery($table, "\nWHERE $where", $limit);
        return $query !== '' ? new QueryListDto(queries: [$query]) :
            new QueryListDto(error: $this->utils()
                ->lang('Unable to build the SQL code for this insert query.'));
    }

    /**
     * Delete one or more items in a table
     *
     * @param string $table         The table name
     * @param array  $options       The query options
     *
     * @return ExecResultDto
     */
    public function deleteRow(string $table, array $options): ExecResultDto
    {
        $execOptions = new ExecOptions(true, true);
        $execOptions->setExecOptions(true, true, false);
        $execOptions->withLogger = true;

        $delete = $this->getDeleteRowQuery($table, $options);
        $result = $this->processor->executeQueryList($delete, $execOptions);
        if ($result->error === null) {
            $result->message = $this->utils()->lang('The item was deleted.');
        }

        return $result;
    }

    /**
     * Get data for import
     *
     * @return array
     */
    public function getImportOptions(): array
    {
        return $this->import()->getOptions();
    }

    /**
     * @param QueryStreamDto $stream
     * @param array $sqlLines
     *
     * @return bool
     */
    private function readLineFromArray(QueryStreamDto $stream, array $sqlLines): bool
    {
        if (!isset($sqlLines[$stream->lineNumber])) {
            return false;
        }

        $stream->queryLine = $sqlLines[$stream->lineNumber++];
        return true;
    }

    /**
     * Execute a set of queries
     *
     * @param string $queries       The queries to execute
     * @param ExecOptions $options
     *
     * @return ExecResultDto
     */
    public function executeQueriesInText(string $queries, ExecOptions $options): ExecResultDto
    {
        $options->setExecOptions(false, false, true);

        $sqlLines = explode("\n", $queries);
        $queryLineReader = fn(QueryStreamDto $stream) =>
            $this->readLineFromArray($stream, $sqlLines);
        $stream = new QueryStreamDto($queryLineReader);

        return $this->processor->executeQueryStream($stream, $options);
    }

    /**
     * @param QueryStreamDto $stream
     * @param resource $fileStream
     *
     * @return bool
     */
    private function readLineFromFile(QueryStreamDto $stream, mixed $fileStream): bool
    {
        if (!($queryLine = fgets($fileStream))) {
            return false;
        }

        $stream->queryLine = $queryLine;
        $stream->lineNumber++;
        return true;
    }

    /**
     * @param FileInterface $file
     * @param bool $decompress
     *
     * @return resource
     */
    private function readFile(FileInterface $file, bool $decompress = false): mixed
    {
        // $compressed = preg_match('~\.gz$~', $file->path());
        // if (!$decompress || !$compressed) {
            //! may not be reachable because of open_basedir
        // }

        return $file->filesystem()->readStream($file->path());
    }

    /**
     * Run queries from uploaded files
     *
     * @param FileInterface $file         The uploaded file
     * @param ExecOptions $options
     *
     * @return ExecResultDto
     */
    public function executeQueriesInFile(FileInterface $file, ExecOptions $options): ExecResultDto
    {
        $options->setExecOptions(false, true, false);

        $fileStream = $this->readFile($file, $options->decompressFile);
        $queryLineReader = fn(QueryStreamDto $stream) =>
            $this->readLineFromFile($stream, $fileStream);
        $stream = new QueryStreamDto($queryLineReader);

        return $this->processor->executeQueryStream($stream, $options);
    }
}

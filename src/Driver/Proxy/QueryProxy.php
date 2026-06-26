<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Jaxon\Request\Upload\FileInterface;
use Lagdo\DbAdmin\Driver\Sql\Connection\QueryResultInterface;
use Lagdo\DbAdmin\Driver\Sql\Dto\QueryClauseDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\SelectDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\QueryOptions;
use Lagdo\DbAdmin\Support\Driver\UiDto\QueryResultDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dml\RowDataReader;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dml\RowDataWriter;
use Lagdo\DbAdmin\Support\Driver\UiDto\QueryListDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\TableImport;
use Lagdo\DbAdmin\Support\Service\Query\QueryStream;

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
     * @param string $table
     *
     * @return array
     */
    public function getInsertData(string $table): array
    {
        $action = 'read';
        $operation = 'insert';

        $reader = $this->reader($action, $operation);
        [$columns,] = $reader->getTableColumns($table);
        if (empty($columns)) {
            return [
                'error' => $this->utils()->lang('You have no privileges to update this table.'),
            ];
        }

        // No data when inserting a new row
        $writer = $this->writer($action, $operation);
        return [
            'columns' => $writer->getInputValues($columns),
        ];
    }

    /**
     * Get data for update/delete of a single row.
     *
     * @param string $table
     * @param array  $rowIdValues
     *
     * @return array
     */
    public function getRowForUpdate(string $table, array $rowIdValues = []): array
    {
        $action = 'read';
        $operation = 'update';

        $reader = $this->reader($action, $operation);
        [$columns, $where, $error] = $reader->getTableColumns($table, $rowIdValues);
        if ($error !== null) {
            return ['error' => $error];
        }
        if (empty($columns) || !$where) {
            $error = $this->utils()->lang('You have no privileges to update this table.');
            return ['error' => $error];
        }

        $select = $reader->getSelectClauses($columns);
        if (count($select) === 0) {
            $error = $this->utils()->lang('Unable to find the data row.'); // No data
            return ['error' => $error];
        }

        $result = $this->findRow($table, $select, [$where]);
        if ($result->hasError() || !($row = $result->fetchAssoc())) {
            $error = $this->utils()->lang('Unable to find the data row.'); // No data
            return ['error' => $error];
        }

        $writer = $this->writer($action, $operation);
        return ['columns' => $writer->getInputValues($columns, $row)];
    }

    /**
     * Build the SQL query to insert a new item in a table
     *
     * @param string $table
     * @param array  $values        
     *
     * @return QueryListDto
     */
    public function getInsertRowQuery(string $table, array $values): QueryListDto
    {
        $action = 'save';
        $operation = 'insert';

        $reader = $this->reader($action, $operation);
        [$columns,] = $reader->getTableColumns($table);
        $values = $reader->getInputValues($columns, $values);
        $query = $this->statement()->getInsertRowQuery($table, $values);

        return $query !== '' ? new QueryListDto(queries: [$query]) :
            new QueryListDto(error: $this->utils()
                ->lang('Unable to build the SQL code for this insert query.'));
    }

    /**
     * Insert a new item in a table
     *
     * @param string $table
     * @param array  $values        
     *
     * @return QueryResultDto
     */
    public function insertItem(string $table, array $values): QueryResultDto
    {
        $options = new QueryOptions(true, true);
        $options->setExecOptions(true, true, false);
        $options->withLogger = true;

        $insert = $this->getInsertRowQuery($table, $values);
        $result = $this->processor->executeQueryList($insert, $options);
        if ($result->error === null) {
            $lastId = $this->engine()->lastAutoIncrementId();
            $lastId = $lastId ? " $lastId" : '';
            $result->message = $this->utils()->lang('The item%s was inserted.', $lastId);
        }

        return $result;
    }

    /**
     * Build the SQL query to update one or more items in a table
     *
     * @param string $table
     * @param array  $rowIdValues
     * @param array  $values        
     *
     * @return QueryListDto
     */
    public function getUpdateRowQuery(string $table, array $rowIdValues, array $values): QueryListDto
    {
        $action = 'save';
        $operation = 'update';

        $reader = $this->reader($action, $operation);
        [$columns, $where, $error] = $reader->getTableColumns($table, $rowIdValues);
        if ($error !== null) {
            return new QueryListDto(error: $error);
        }

        $values = $reader->getInputValues($columns, $values);
        $limit = $reader->getQueryLimit($table, $rowIdValues);
        $query = $this->statement()->getUpdateRowQuery($table, $values, "\nWHERE $where", $limit);

        return $query !== '' ? new QueryListDto(queries: [$query]) :
            new QueryListDto(error: $this->utils()
                ->lang('Unable to build the SQL code for this insert query.'));
    }

    /**
     * Update one or more items in a table
     *
     * @param string $table
     * @param array  $rowIdValues
     * @param array  $values        
     *
     * @return QueryResultDto
     */
    public function updateRow(string $table, array $rowIdValues, array $values): QueryResultDto
    {
        $options = new QueryOptions(true, true);
        $options->setExecOptions(true, true, false);
        $options->withLogger = true;

        $update = $this->getUpdateRowQuery($table, $rowIdValues, $values);
        $result = $this->processor->executeQueryList($update, $options);
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
     * Build the SQL query to delete one or more items in a table
     *
     * @param string $table
     * @param array  $rowIdValues
     *
     * @return QueryListDto
     */
    public function getDeleteRowQuery(string $table, array $rowIdValues): QueryListDto
    {
        $action = 'save';
        $operation = 'update';

        $reader = $this->reader($action, $operation);
        [, $where, $error] = $reader->getTableColumns($table, $rowIdValues);
        if ($error !== null) {
            return new QueryListDto(error: $error);
        }

        $limit = $reader->getQueryLimit($table, $rowIdValues);
        $query = $this->statement()->getDeleteRowQuery($table, "\nWHERE $where", $limit);

        return $query !== '' ? new QueryListDto(queries: [$query]) :
            new QueryListDto(error: $this->utils()
                ->lang('Unable to build the SQL code for this insert query.'));
    }

    /**
     * Delete one or more items in a table
     *
     * @param string $table
     * @param array  $rowIdValues
     *
     * @return QueryResultDto
     */
    public function deleteRow(string $table, array $rowIdValues): QueryResultDto
    {
        $options = new QueryOptions(true, true);
        $options->setExecOptions(true, true, false);
        $options->withLogger = true;

        $delete = $this->getDeleteRowQuery($table, $rowIdValues);
        $result = $this->processor->executeQueryList($delete, $options);
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
     * @param QueryStream $stream
     * @param array $sqlLines
     *
     * @return bool
     */
    private function readLineFromArray(QueryStream $stream, array $sqlLines): bool
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
     * @param QueryOptions $options
     *
     * @return QueryResultDto
     */
    public function executeQueriesInText(string $queries, QueryOptions $options): QueryResultDto
    {
        $options->setExecOptions(false, false, true);

        $sqlLines = explode("\n", $queries);
        $queryLineReader = fn(QueryStream $stream) =>
            $this->readLineFromArray($stream, $sqlLines);
        $stream = new QueryStream($queryLineReader);

        return $this->processor->executeQueryStream($stream, $options);
    }

    /**
     * @param QueryStream $stream
     * @param resource $fileStream
     *
     * @return bool
     */
    private function readLineFromFile(QueryStream $stream, mixed $fileStream): bool
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
     * @param QueryOptions $options
     *
     * @return QueryResultDto
     */
    public function executeQueriesInFile(FileInterface $file, QueryOptions $options): QueryResultDto
    {
        $options->setExecOptions(false, true, false);

        $fileStream = $this->readFile($file, $options->decompressFile);
        $queryLineReader = fn(QueryStream $stream) =>
            $this->readLineFromFile($stream, $fileStream);
        $stream = new QueryStream($queryLineReader);

        return $this->processor->executeQueryStream($stream, $options);
    }
}

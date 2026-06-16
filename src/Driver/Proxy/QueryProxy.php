<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Jaxon\Request\Upload\FileInterface;
use Lagdo\DbAdmin\Driver\Sql\Dto\QueryCodeDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\ExecOptions;
use Lagdo\DbAdmin\Support\Driver\UiDto\ExecResultDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dml\ColumnInput;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dml\ColumnValue;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dml\RowDataReader;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dml\RowDataWriter;
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
     * @var string
     * read => edit action for single row insert, update or delete
     * save => save action for insert, update or delete
     * select => edit action for bulk update
     * clone => clone a selected set of data rows
     */
    private string $action;

    /**
     * @var string
     */
    private string $operation;

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
     * @return RowDataWriter
     */
    private function _writer(): RowDataWriter
    {
        $columnValue = (new ColumnValue($this))->init($this->action, $this->operation);
        $columnInput = (new ColumnInput($this))->init($this->action, $this->operation);
        return (new RowDataWriter($this))->init($this->action, $this->operation,
            $columnValue, $columnInput);
    }

    /**
     * @return RowDataWriter
     */
    private function writer(): RowDataWriter
    {
        return $this->writer ??= $this->_writer();
    }

    /**
     * @return RowDataReader
     */
    private function reader(): RowDataReader
    {
        return $this->reader ??= (new RowDataReader($this))->init($this->action, $this->operation);
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
        $this->action = 'read';
        $this->operation = 'insert';

        [$columns,] = $this->reader()->getTableColumns($table, $options);
        if (empty($columns)) {
            return [
                'error' => $this->utils()->lang('You have no privileges to update this table.'),
            ];
        }

        // No data when inserting a new row
        return [
            'columns' => $this->writer()->getInputValues($columns, $options),
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
    public function getUpdateData(string $table, array $options = []): array
    {
        $this->action = 'read';
        $this->operation = 'update';

        // From edit.inc.php
        [$columns, $where] = $this->reader()->getTableColumns($table, $options);
        if (empty($columns) || !$where) {
            return [
                'error' => $this->utils()->lang('You have no privileges to update this table.'),
            ];
        }

        // From edit.inc.php
        $select = $this->reader()->getSelectClauses($columns);
        if (count($select) === 0) {
            return [
                'error' => $this->utils()->lang('Unable to find the edited data row.'),
            ]; // No data
        }

        $result = $this->engine()->select($table, $select, [$where],
            $select, [], $this->action === 'select' ? 2 : 1);
        if ($result->hasError()) {
            return [
                'error' => $this->engine()->error(),
            ]; // Error
        }

        $row = $result->fetchAssoc();
        if($this->action === 'select' && (!$row || $result->fetchAssoc()))
        {
            return [
                'error' => $this->utils()->lang('Unable to find the edited data row.'),
            ]; // No data
        }

        return [
            'columns' => $this->writer()->getInputValues($columns, $row),
        ];
    }

    /**
     * Build the SQL query to insert a new item in a table
     *
     * @param string $table         The table name
     * @param array  $options       The query options
     * @param array  $values        The updated values
     *
     * @return array
     */
    public function getInsertRowQuery(string $table, array $options, array $values): array
    {
        $this->action = 'save';
        $this->operation = 'insert';

        [$columns,] = $this->reader()->getTableColumns($table, $options);
        $values = $this->reader()->getInputValues($columns, $values);
        $query = $this->statement()->getInsertRowQuery($table, $values);

        return $query !== '' ? [
            'query' => $query,
        ] : [
            'error' => $this->utils()->lang('Unable to build the SQL code for this insert query.'),
        ];
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
        $insert = $this->getInsertRowQuery($table, $options, $values);

        $result = $this->processor->executeLibraryQueries($insert);
        if ($result->error === null) {
            $lastId = $this->engine()->lastAutoIncrementId();
            $result->message = $this->utils()->lang('Item%s has been inserted.',
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
     * @return array
     */
    public function getUpdateRowQuery(string $table, array $options, array $values): array
    {
        $this->action = 'save';
        $this->operation = 'update';

        [$columns, $where] = $this->reader()->getTableColumns($table, $options);
        $values = $this->reader()->getInputValues($columns, $values);
        $limit = $this->reader()->getQueryLimit($table, $options);

        $query = $this->statement()->getUpdateRowQuery($table, $values, "\nWHERE $where", $limit);
        return $query !== '' ? [
            'query' => $query,
        ] : [
            'error' => $this->utils()->lang('Unable to build the SQL code for this insert query.'),
        ];
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
    public function updateItem(string $table, array $options, array $values): ExecResultDto
    {
        $update = $this->getUpdateRowQuery($table, $options, $values);

        $result = $this->processor->executeLibraryQueries($update);
        if ($result->error === null) {
            $result->message = $this->utils()->lang('Item has been updated.');
        }
        return $result;
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
    public function getUpdatedItem(string $table, array $options, array $values): array
    {
        $this->action = 'save';
        $this->operation = 'update';

        [$columns, $where] = $this->reader()->getTableColumns($table, $options);
        $values = $this->reader()->getInputValues($columns, $values);

        // Get the modified data
        // Todo: check if the values in the where clause are changed.
        $result = $this->engine()->select($table, array_keys($values), [$where]);
        if ($result->hasError()) {
            return [
                'warning' => $this->utils()->lang('Unable to read the updated row.'),
            ];
        }

        $row = $result->fetchAssoc();
        return [
            'cols' => $this->writer()->getUpdatedRow($row, $columns, $options),
        ];
    }

    /**
     * Build the SQL query to delete one or more items in a table
     *
     * @param string $table         The table name
     * @param array  $options       The query options
     *
     * @return array
     */
    public function getDeleteRowQuery(string $table, array $options): array
    {
        $this->action = 'save';
        $this->operation = 'update';

        [, $where] = $this->reader()->getTableColumns($table, $options);
        $limit = $this->reader()->getQueryLimit($table, $options);

        $query = $this->statement()->getDeleteRowQuery($table, "\nWHERE $where", $limit);
        return $query !== '' ? [
            'query' => $query,
        ] : [
            'error' => $this->utils()->lang('Unable to build the SQL code for this insert query.'),
        ];
    }

    /**
     * Delete one or more items in a table
     *
     * @param string $table         The table name
     * @param array  $options       The query options
     *
     * @return ExecResultDto
     */
    public function deleteItem(string $table, array $options): ExecResultDto
    {
        $delete = $this->getDeleteRowQuery($table, $options);

        $result = $this->processor->executeLibraryQueries($delete);
        if ($result->error === null) {
            $result->message = $this->utils()->lang('Item has been deleted.');
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
     * @param QueryCodeDto $dto
     * @param array $sqlLines
     *
     * @return bool
     */
    private function readLineFromText(QueryCodeDto $dto, array $sqlLines): bool
    {
        if (!isset($sqlLines[$dto->lineNumber])) {
            return false;
        }

        $dto->queryLine = $sqlLines[$dto->lineNumber++];
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
        $queryLineReader = fn(QueryCodeDto $dto) => $this->readLineFromText($dto, $sqlLines);
        $queryDto = new QueryCodeDto($queryLineReader);

        return $this->processor->executeUserQueries($queryDto, $options);
    }

    /**
     * @param QueryCodeDto $dto
     * @param resource $fileStream
     *
     * @return bool
     */
    private function readLineFromFile(QueryCodeDto $dto, mixed $fileStream): bool
    {
        if (!($queryLine = fgets($fileStream))) {
            return false;
        }

        $dto->queryLine = $queryLine;
        $dto->lineNumber++;
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
        $queryLineReader = fn(QueryCodeDto $dto) => $this->readLineFromFile($dto, $fileStream);
        $queryDto = new QueryCodeDto($queryLineReader);

        return $this->processor->executeUserQueries($queryDto, $options);
    }
}

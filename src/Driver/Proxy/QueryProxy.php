<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dml\ColumnInput;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dml\ColumnValue;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dml\RowDataReader;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dml\RowDataWriter;

use function array_filter;
use function array_keys;
use function array_map;
use function count;

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
     * @return RowDataWriter
     */
    private function writer(): RowDataWriter
    {
        $columnValue = (new ColumnValue($this))->init($this->action, $this->operation);
        $columnInput = (new ColumnInput($this))->init($this->action, $this->operation);
        return (new RowDataWriter($this))->init($this->action, $this->operation,
            $columnValue, $columnInput);
    }

    /**
     * @return RowDataReader
     */
    private function reader(): RowDataReader
    {
        return new RowDataReader($this);
    }

    /**
     * Get the table columns
     *
     * @param string $table         The table name
     * @param array  $options       The query options
     *
     * @return array
     */
    private function getColumns(string $table, array $options): array
    {
        // From edit.inc.php
        $columns = $this->engine()->columns($table);
        // Important: get the where clauses before filtering the columns.
        $where = $this->operation === 'insert' ? [] :
            $this->engine()->where($options, $columns);
        // Remove columns without the required privilege, or that cannot be edited.
        $columns = array_filter($columns, fn(ColumnDto $column) =>
            isset($column->privileges[$this->operation]) &&
            $this->pageUi()->columnName($column) !== '' && !$column->generated);

        return [$columns, $where];
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

        [$columns,] = $this->getColumns($table, $options);
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
     * @param array<ColumnDto> $columns
     *
     * @return array
     */
    private function getRowSelectClauses(array $columns): array
    {
        // if (!$this->engine()->support('table')) {
        //     return ['*'];
        // }

        // From edit.inc.php
        $columns = array_filter($columns,
            fn(ColumnDto $column) => isset($column->privileges['select']));
        return array_map(function(ColumnDto $column, string $name) {
            $as = $this->action === 'clone' && $column->autoIncrement ? "''" :
                $this->statement()->convertColumn($column);
            return ($as !== '' ? "$as AS " : '') . $this->statement()->escapeId($name);
        }, $columns, array_keys($columns));
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
        [$columns, $where] = $this->getColumns($table, $options);
        if (empty($columns) || !$where) {
            return [
                'error' => $this->utils()->lang('You have no privileges to update this table.'),
            ];
        }

        // From edit.inc.php
        $select = $this->getRowSelectClauses($columns);
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
            // $result->rowCount() != 1 isn't available in all drivers
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
    public function getRowInsertQuery(string $table, array $options, array $values): array
    {
        $this->action = 'save';
        $this->operation = 'insert';

        [$columns,] = $this->getColumns($table, $options);
        $values = $this->reader()->getInputValues($columns, $values);

        $query = $this->statement()->getRowInsertQuery($table, $values);
        return $query !== '' ? ['query' => $query] : [
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
     * @return array
     */
    public function insertItem(string $table, array $options, array $values): array
    {
        $this->action = 'save';
        $this->operation = 'insert';

        [$columns,] = $this->getColumns($table, $options);
        $values = $this->reader()->getInputValues($columns, $values);

        if (!$this->engine()->insert($table, $values)) {
            return [
                'error' => $this->engine()->error(),
            ];
        }

        $lastId = $this->engine()->lastAutoIncrementId();
        return [
            'message' => $this->utils()->lang('Item%s has been inserted.',
                $lastId ? " $lastId" : ''),
        ];
    }

    /**
     * @param string $table
     * @param array $options
     *
     * @return int
     */
    private function getQueryLimit(string $table, array $options): int
    {
        // From edit.inc.php
        $indexes = $this->engine()->indexes($table);
        $uniqueIds = $this->utils()->uniqueIds($options['where'], $indexes);
        return count($uniqueIds ?? []) === 0 ? 1 : 0; // Limit to 1 if no unique ids are found.
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
    public function getRowUpdateQuery(string $table, array $options, array $values): array
    {
        $this->action = 'save';
        $this->operation = 'update';

        [$columns, $where] = $this->getColumns($table, $options);
        $values = $this->reader()->getInputValues($columns, $values);
        $limit = $this->getQueryLimit($table, $options);

        $query = $this->statement()->getRowUpdateQuery($table, $values, "\nWHERE $where", $limit);
        return $query !== '' ? ['query' => $query] : [
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
     * @return array
     */
    public function updateItem(string $table, array $options, array $values): array
    {
        $this->action = 'save';
        $this->operation = 'update';

        [$columns, $where] = $this->getColumns($table, $options);
        $values = $this->reader()->getInputValues($columns, $values);
        $limit = $this->getQueryLimit($table, $options);

        if (!$this->engine()->update($table, $values, "\nWHERE $where", $limit)) {
            return [
                'error' => $this->engine()->error(),
            ];
        }

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
            'message' => $this->utils()->lang('Item has been updated.'),
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
    public function getRowDeleteQuery(string $table, array $options): array
    {
        $this->action = 'save';
        $this->operation = 'update';

        [, $where] = $this->getColumns($table, $options);
        $limit = $this->getQueryLimit($table, $options);

        $query = $this->statement()->getRowDeleteQuery($table, "\nWHERE $where", $limit);
        return $query !== '' ? ['query' => $query] : [
            'error' => $this->utils()->lang('Unable to build the SQL code for this insert query.'),
        ];
    }

    /**
     * Delete one or more items in a table
     *
     * @param string $table         The table name
     * @param array  $options       The query options
     *
     * @return array
     */
    public function deleteItem(string $table, array $options): array
    {
        $this->action = 'save';
        $this->operation = 'update';

        [, $where] = $this->getColumns($table, $options);
        $limit = $this->getQueryLimit($table, $options);

        if (!$this->engine()->delete($table, "\nWHERE $where", $limit)) {
            return [
                'error' => $this->engine()->error(),
            ];
        }

        return [
            'message' => $this->utils()->lang('Item has been deleted.'),
        ];
    }
}

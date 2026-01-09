<?php

namespace Lagdo\DbAdmin\Db\Driver\Facades;

use Lagdo\DbAdmin\Db\UiData\Dml\DataFieldInput;
use Lagdo\DbAdmin\Db\UiData\Dml\DataFieldValue;
use Lagdo\DbAdmin\Db\UiData\Dml\DataRowReader;
use Lagdo\DbAdmin\Db\UiData\Dml\DataRowWriter;
use Lagdo\DbAdmin\Driver\Dto\TableFieldDto;

use function count;

/**
 * Facade to table query functions
 */
class QueryFacade extends AbstractFacade
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
     * @return DataRowWriter
     */
    private function writer(): DataRowWriter
    {
        $fieldValue = new DataFieldValue($this->page, $this->driver,
            $this->utils, $this->action, $this->operation);
        $fieldInput = new DataFieldInput($this->page, $this->driver,
            $this->utils, $this->action, $this->operation);
        return new DataRowWriter($this->page, $this->driver, $this->utils,
            $this->action, $this->operation, $fieldValue, $fieldInput);
    }

    /**
     * @return DataRowReader
     */
    private function reader(): DataRowReader
    {
        return new DataRowReader($this->page, $this->driver, $this->utils);
    }

    /**
     * Get the table fields
     *
     * @param string $table         The table name
     * @param array  $options       The query options
     *
     * @return array
     */
    private function getFields(string $table, array $options): array
    {
        // From edit.inc.php
        $fields = $this->driver->fields($table);
        // Important: get the where clauses before filtering the fields.
        $where = $this->operation === 'insert' ? [] :
            $this->driver->where($options, $fields);
        // Remove fields without the required privilege, or that cannot be edited.
        $fields = array_filter($fields, fn(TableFieldDto $field) =>
            isset($field->privileges[$this->operation]) &&
            $this->page->fieldName($field) !== '' && !$field->generated);

        return [$fields, $where];
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

        [$fields,] = $this->getFields($table, $options);
        if (empty($fields)) {
            return [
                'error' => $this->utils->trans->lang('You have no privileges to update this table.'),
            ];
        }

        // No data when inserting a new row
        return [
            'fields' => $this->writer()->getInputValues($fields, $options),
        ];
    }

    /**
     * @param array<TableFieldDto> $fields
     *
     * @return array
     */
    private function getRowSelectClauses(array $fields): array
    {
        // if (!$this->driver->support("table")) {
        //     return ["*"];
        // }

        // From edit.inc.php
        $select = [];
        foreach ($fields as $name => $field) {
            if (isset($field->privileges["select"])) {
                $as = $this->action === 'clone' && $field->autoIncrement ? "''" :
                    $this->driver->convertField($field);
                $select[] = ($as ? "$as AS " : "") . $this->driver->escapeId($name);
            }
        }
        return $select;
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
        [$fields, $where] = $this->getFields($table, $options);
        if (empty($fields) || !$where) {
            return [
                'error' => $this->utils->trans->lang('You have no privileges to update this table.'),
            ];
        }

        // From edit.inc.php
        $select = $this->getRowSelectClauses($fields);
        if (count($select) === 0) {
            return [
                'error' => $this->utils->trans->lang('Unable to find the edited data row.'),
            ]; // No data
        }

        $statement = $this->driver->select($table, $select, [$where],
            $select, [], $this->action === 'select' ? 2 : 1);
        if (!$statement) {
            return [
                'error' => $this->driver->error(),
            ]; // Error
        }

        $rowData = $statement->fetchAssoc();
        if($this->action === 'select' && (!$rowData || $statement->fetchAssoc()))
        {
            // $statement->rowCount() != 1 isn't available in all drivers
            return [
                'error' => $this->utils->trans->lang('Unable to find the edited data row.'),
            ]; // No data
        }

        return [
            'fields' => $this->writer()->getInputValues($fields, $rowData),
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
    public function getInsertQuery(string $table, array $options, array $values): array
    {
        $this->action = 'save';
        $this->operation = 'insert';

        [$fields,] = $this->getFields($table, $options);
        $values = $this->reader()->getInputValues($fields, $values);

        $query = $this->driver->getInsertQuery($table, $values);
        return $query !== '' ? ['query' => $query] : [
            'error' => $this->utils->trans->lang('Unable to build the SQL code for this insert query.'),
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

        [$fields,] = $this->getFields($table, $options);
        $values = $this->reader()->getInputValues($fields, $values);

        if (!$this->driver->insert($table, $values)) {
            return [
                'error' => $this->driver->error(),
            ];
        }

        $lastId = $this->driver->lastAutoIncrementId();
        return [
            'message' => $this->utils->trans->lang('Item%s has been inserted.',
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
        $indexes = $this->driver->indexes($table);
        $uniqueIds = $this->utils->uniqueIds($options['where'], $indexes);
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
    public function getUpdateQuery(string $table, array $options, array $values): array
    {
        $this->action = 'save';
        $this->operation = 'update';

        [$fields, $where] = $this->getFields($table, $options);
        $values = $this->reader()->getInputValues($fields, $values);
        $limit = $this->getQueryLimit($table, $options);

        $query = $this->driver->getUpdateQuery($table, $values, "\nWHERE $where", $limit);
        return $query !== '' ? ['query' => $query] : [
            'error' => $this->utils->trans->lang('Unable to build the SQL code for this insert query.'),
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

        [$fields, $where] = $this->getFields($table, $options);
        $values = $this->reader()->getInputValues($fields, $values);
        $limit = $this->getQueryLimit($table, $options);

        if (!$this->driver->update($table, $values, "\nWHERE $where", $limit)) {
            return [
                'error' => $this->driver->error(),
            ];
        }

        // Get the modified data
        // Todo: check if the values in the where clause are changed.
        $statement = $this->driver->select($table, array_keys($values), [$where]);
        $result = !$statement ? null : $statement->fetchAssoc();
        if (!$result) {
            return [
                'warning' => $this->utils->trans->lang('Unable to read the updated row.'),
            ];
        }

        return [
            'cols' => $this->writer()->getUpdatedRow($result, $fields, $options),
            'message' => $this->utils->trans->lang('Item has been updated.'),
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
    public function getDeleteQuery(string $table, array $options): array
    {
        $this->action = 'save';
        $this->operation = 'update';

        [, $where] = $this->getFields($table, $options);
        $limit = $this->getQueryLimit($table, $options);

        $query = $this->driver->getDeleteQuery($table, "\nWHERE $where", $limit);
        return $query !== '' ? ['query' => $query] : [
            'error' => $this->utils->trans->lang('Unable to build the SQL code for this insert query.'),
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

        [, $where] = $this->getFields($table, $options);
        $limit = $this->getQueryLimit($table, $options);

        if (!$this->driver->delete($table, "\nWHERE $where", $limit)) {
            return [
                'error' => $this->driver->error(),
            ];
        }

        return [
            'message' => $this->utils->trans->lang('Item has been deleted.'),
        ];
    }
}

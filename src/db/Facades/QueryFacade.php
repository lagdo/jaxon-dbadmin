<?php

namespace Lagdo\DbAdmin\Db\Facades;

use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function compact;
use function is_string;
use function is_array;
use function count;
use function preg_match;

/**
 * Facade to table query functions
 */
class QueryFacade extends AbstractFacade
{
    use Traits\QueryInputTrait;
    use Traits\QueryTrait;

    /**
     * Get data for an input field
     *
     * @param TableFieldEntity $field
     * @param mixed $value
     * @param string|null $function
     * @param array $options
     *
     * @return array
     */
    protected function getFieldInput(TableFieldEntity $field, $value, $function, array $options): array
    {
        // From functions.inc.php (function input($field, $value, $function))
        $name = $this->admin->html($this->admin->bracketEscape($field->name));
        $save = $options['save'];
        $reset = ($this->driver->jush() == 'mssql' && $field->autoIncrement);
        if (is_array($value) && !$function) {
            $value = json_encode($value, JSON_PRETTY_PRINT);
            $function = 'json';
        }
        if ($reset && !$save) {
            $function = null;
        }
        $functions = [];
        if ($reset) {
            $functions['orig'] = $this->trans->lang('original');
        }
        $functions += $this->admin->editFunctions($field);
        return [
            'type' => $this->admin->html($field->fullType),
            'name' => $name,
            'field' => [
                'type' => $field->type,
            ],
            'functions' => $this->getEntryFunctions($field, $name, $function, $functions),
            'input' => $this->getEntryInput($field, $name, $value, $function, $functions, $options),
        ];
    }

    /**
     * @param array $fields
     * @param array|null $row
     * @param string $update
     * @param array $queryOptions
     *
     * @return array
     */
    private function getQueryEntries(array $fields, $row, string $update, array $queryOptions): array
    {
        $entries = [];
        foreach ($fields as $name => $field) {
            $value = $this->getRowFieldValue($field, $name, $row, $update, $queryOptions);
            $function = $this->getRowFieldFunction($field, $name, $value, $update, $queryOptions);
            if (preg_match('~time~', $field->type) && is_string($value) &&
                preg_match('~^CURRENT_TIMESTAMP~i', $value)) {
                $value = '';
                $function = 'now';
            }
            $entries[$name] = $this->getFieldInput($field, $value, $function, $queryOptions);
        }
        return $entries;
    }

    /**
     * Get data for insert/update on a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function getQueryData(string $table, array $queryOptions = []): array
    {
        // Default options
        $queryOptions['clone'] = false;
        $queryOptions['save'] = false;

        [$fields, $where, $update] = $this->getFields($table, $queryOptions);
        $row = $this->getQueryFirstRow($table, $where, $fields, $queryOptions);

        /* TODO: Activate this code when a driver without table support will be supported */
        /*if (!$this->driver->support('table') && empty($fields)) {
            $primary = ''; // $this->driver->primaryIdName();
            if (!$where) {
                // insert
                $statement = $this->driver->select($table, ['*'], [$where], ['*']);
                $row = ($statement ? $statement->fetchAssoc() : false);
                if (!$row) {
                    $row = [$primary => ''];
                }
            }
            if ($row) {
                foreach ($row as $key => $val) {
                    if (!$where) {
                        $row[$key] = null;
                    }
                    $fields[$key] = [
                        'name' => $key,
                        'null' => ($key !== $primary),
                        'autoIncrement' => ($key === $primary)
                    ];
                }
            }
        }*/

        // From functions.inc.php (function edit_form($table, $fields, $row, $update))
        $entries = [];
        $tableName = $this->admin->tableName($this->driver->tableStatusOrName($table, true));
        $error = null;
        if (($where) && $row === null) { // No row found to edit.
            $error = $this->trans->lang('No rows.');
        } elseif (empty($fields)) {
            $error = $this->trans->lang('You have no privileges to update this table.');
        } else {
            $entries = $this->getQueryEntries($fields, $row, $update, $queryOptions);
        }

        $fields = $entries;
        return compact('tableName', 'error', 'fields');
    }

    /**
     * Insert a new item in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function insertItem(string $table, array $queryOptions): array
    {
        list($fields, ,) = $this->getFields($table, $queryOptions);

        // From edit.inc.php
        $values = [];
        foreach ($fields as $name => $field) {
            $val = $this->admin->processInput($field, $queryOptions);
            if ($val !== false && $val !== null) {
                $values[$this->driver->escapeId($name)] = $val;
            }
        }

        $result = $this->driver->insert($table, $values);
        $lastId = ($result ? $this->driver->lastAutoIncrementId() : 0);
        $message = $this->trans->lang('Item%s has been inserted.', ($lastId ? " $lastId" : ''));

        $error = $this->driver->error();

        return compact('result', 'message', 'error');
    }

    /**
     * Update one or more items in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function updateItem(string $table, array $queryOptions): array
    {
        list($fields, $where, ) = $this->getFields($table, $queryOptions);

        // From edit.inc.php
        $indexes = $this->driver->indexes($table);
        $uniqueIds = $this->admin->uniqueIds($queryOptions['where'], $indexes);
        $queryWhere = "\nWHERE $where";

        $values = [];
        foreach ($fields as $name => $field) {
            $val = $this->admin->processInput($field, $queryOptions);
            if ($val !== false && $val !== null) {
                $values[$this->driver->escapeId($name)] = $val;
            }
        }

        $result = $this->driver->update($table, $values, $queryWhere, count($uniqueIds));
        $message = $this->trans->lang('Item has been updated.');

        $error = $this->driver->error();

        return compact('result', 'message', 'error');
    }

    /**
     * Delete one or more items in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function deleteItem(string $table, array $queryOptions): array
    {
        list($fields, $where, $update) = $this->getFields($table, $queryOptions);

        // From edit.inc.php
        $indexes = $this->driver->indexes($table);
        $uniqueIds = $this->admin->uniqueIds($queryOptions['where'], $indexes);
        $queryWhere = "\nWHERE $where";

        $result = $this->driver->delete($table, $queryWhere, count($uniqueIds));
        $message = $this->trans->lang('Item has been deleted.');

        $error = $this->driver->error();

        return compact('result', 'message', 'error');
    }
}

<?php

namespace Lagdo\DbAdmin\DbAdmin;

use Lagdo\DbAdmin\DbAdmin\Traits\QueryInputTrait;

use function compact;
use function is_array;
use function count;
use function preg_match;
use function is_bool;
use function array_sum;

/**
 * Admin table query functions
 */
class TableQueryAdmin extends AbstractAdmin
{
    use QueryInputTrait;

    /**
     * Get the table fields
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    private function getFields(string $table, array $queryOptions): array
    {
        // From edit.inc.php
        $fields = $this->driver->fields($table);

        //!!!! $queryOptions["select"] is never set here !!!!//

        $where = $this->admin->where($queryOptions, $fields);
        $update = $where;
        foreach ($fields as $name => $field) {
            $generated = $field->generated ?? false;
            if (!isset($field->privileges[$update ? "update" : "insert"]) ||
                $this->util->fieldName($field) == "" || $generated) {
                unset($fields[$name]);
            }
        }

        return [$fields, $where, $update];
    }

    /**
     * @param array $fields
     * @param array $queryOptions
     *
     * @return array
     */
    private function getQuerySelect(array $fields, array $queryOptions): array
    {
        $select = [];
        foreach ($fields as $name => $field) {
            if (isset($field->privileges["select"])) {
                $as = $this->driver->convertField($field);
                if ($queryOptions["clone"] && $field->autoIncrement) {
                    $as = "''";
                }
                if ($this->driver->jush() == "sql" && preg_match("~enum|set~", $field->type)) {
                    $as = "1*" . $this->driver->escapeId($name);
                }
                $select[] = ($as ? "$as AS " : "") . $this->driver->escapeId($name);
            }
        }
        if (!$this->driver->support("table")) {
            $select = ["*"];
        }
        return $select;
    }

    /**
     * @param string $table
     * @param string $where
     * @param array $fields
     * @param array $queryOptions
     *
     * @return array|null
     */
    private function getQueryFirstRow(string $table, string $where, array $fields, array $queryOptions)
    {
        // From edit.inc.php
        $row = null;
        if (($where)) {
            $select = $this->getQuerySelect($fields, $queryOptions);
            $row = [];
            if ($select) {
                $statement = $this->driver->select($table, $select, [$where], $select, [],
                    (isset($queryOptions["select"]) ? 2 : 1));
                if (($statement)) {
                    $row = $statement->fetchAssoc();
                }/* else {
                    $error = $this->driver->error();
                }*/
                // if(isset($queryOptions["select"]) && (!$row || $statement->fetchAssoc()))
                // {
                //     // $statement->rowCount() != 1 isn't available in all drivers
                //     $row = null;
                // }
            }
        }
        return $row;
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
            // $default = $queryOptions["set"][$this->util->bracketEscape($name)] ?? null;
            // if($default === null)
            // {
            $default = $field->default;
            if ($field->type == "bit" && preg_match("~^b'([01]*)'\$~", $default, $regs)) {
                $default = $regs[1];
            }
            // }
            $value = ($row !== null ?
                ($row[$name] != "" && $this->driver->jush() == "sql" && preg_match("~enum|set~", $field->type) ?
                    (is_array($row[$name]) ? array_sum($row[$name]) : +$row[$name]) :
                    (is_bool($row[$name]) ? +$row[$name] : $row[$name])) :
                (!$update && $field->autoIncrement ? "" : (isset($queryOptions["select"]) ? false : $default)));
            $function = ($queryOptions["save"] ? (string)$queryOptions["function"][$name] :
                ($update && preg_match('~^CURRENT_TIMESTAMP~i', $field->onUpdate) ? "now" :
                    ($value === false ? null : ($value !== null ? '' : 'NULL'))));
            if (!$update && $value == $field->default && preg_match('~^[\w.]+\(~', $value)) {
                $function = "SQL";
            }
            if (preg_match("~time~", $field->type) && preg_match('~^CURRENT_TIMESTAMP~i', $value)) {
                $value = "";
                $function = "now";
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
        $isInsert = (count($queryOptions) === 0); // True only on insert.
        // Default options
        $queryOptions['clone'] = false;
        $queryOptions['save'] = false;

        list($fields, $where, $update) = $this->getFields($table, $queryOptions);
        $row = $this->getQueryFirstRow($table, $where, $fields, $queryOptions);

        /* TODO: Activate this code when a driver without table support will be supported */
        /*if (!$this->driver->support("table") && empty($fields)) {
            $primary = ''; // $this->driver->primaryIdName();
            if (!$where) {
                // insert
                $statement = $this->driver->select($table, ["*"], [$where], ["*"]);
                $row = ($statement ? $statement->fetchAssoc() : false);
                if (!$row) {
                    $row = [$primary => ""];
                }
            }
            if ($row) {
                foreach ($row as $key => $val) {
                    if (!$where) {
                        $row[$key] = null;
                    }
                    $fields[$key] = [
                        "name" => $key,
                        "null" => ($key !== $primary),
                        "autoIncrement" => ($key === $primary)
                    ];
                }
            }
        }*/

        // From functions.inc.php (function edit_form($table, $fields, $row, $update))
        $entries = [];
        $tableName = $this->util->tableName($this->driver->tableStatusOrName($table, true));
        $error = null;
        if (($where) && $row === null) { // No row found to edit.
            $error = $this->trans->lang('No rows.');
        } elseif (!$fields) {
            $error = $this->trans->lang('You have no privileges to update this table.');
        } else {
            $entries = $this->getQueryEntries($fields, $row, $update, $queryOptions);
        }

        $mainActions = [
            'query-back' => $this->trans->lang('Back'),
            'query-save' => $this->trans->lang('Save'),
        ];
        if ($isInsert) {
            $mainActions['query-save-select'] = $this->trans->lang('Save and select');
        }

        $fields = $entries;
        return compact('mainActions', 'tableName', 'error', 'fields');
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
            $val = $this->util->processInput($field, $queryOptions);
            if ($val !== false && $val !== null) {
                $values[$this->driver->escapeId($name)] = $val;
            }
        }

        $result = $this->driver->insert($table, $values);
        $lastId = ($result ? $this->driver->lastAutoIncrementId() : 0);
        $message = $this->trans->lang('Item%s has been inserted.', ($lastId ? " $lastId" : ""));

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
        list($fields, $where, $update) = $this->getFields($table, $queryOptions);

        // From edit.inc.php
        $indexes = $this->driver->indexes($table);
        $uniqueIds = $this->util->uniqueIds($queryOptions["where"], $indexes);
        $queryWhere = "\nWHERE $where";

        $values = [];
        foreach ($fields as $name => $field) {
            $val = $this->util->processInput($field, $queryOptions);
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
        $uniqueIds = $this->util->uniqueIds($queryOptions["where"], $indexes);
        $queryWhere = "\nWHERE $where";

        $result = $this->driver->delete($table, $queryWhere, count($uniqueIds));
        $message = $this->trans->lang('Item has been deleted.');

        $error = $this->driver->error();

        return compact('result', 'message', 'error');
    }
}

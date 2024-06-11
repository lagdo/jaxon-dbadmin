<?php

namespace Lagdo\DbAdmin\Db\Facades\Traits;

use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function is_array;
use function preg_match;
use function is_bool;
use function array_sum;

trait QueryTrait
{
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

        $where = $this->driver->where($queryOptions, $fields);
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
    private function getQueryFirstRow(string $table, string $where, array $fields, array $queryOptions): ?array
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
     * @param TableFieldEntity $field
     * @param string $name
     * @param array|null $row
     * @param string $update
     * @param array $queryOptions
     *
     * @return mixed
     */
    private function getRowFieldValue(TableFieldEntity $field, string $name, ?array $row, string $update, array $queryOptions)
    {
        // $default = $queryOptions["set"][$this->util->bracketEscape($name)] ?? null;
        // if($default === null)
        // {
        $default = $field->default;
        if ($field->type == "bit" && preg_match("~^b'([01]*)'\$~", $default, $regs)) {
            $default = $regs[1];
        }
        // }
        if ($row === null) {
            return !$update && $field->autoIncrement ? "" : (isset($queryOptions["select"]) ? false : $default);
        }
        if ($row[$name] != "" && $this->driver->jush() == "sql" && preg_match("~enum|set~", $field->type) ) {
            return is_array($row[$name]) ? array_sum($row[$name]) : +$row[$name];
        }
        return is_bool($row[$name]) ? +$row[$name] : $row[$name];
    }

    /**
     * @param TableFieldEntity $field
     * @param string $name
     * @param mixed $value
     * @param string $update
     * @param array $queryOptions
     *
     * @return string|null
     */
    private function getRowFieldFunction(TableFieldEntity $field, string $name, $value, string $update, array $queryOptions): ?string
    {
        if (!$update && $value == $field->default && preg_match('~^[\w.]+\(~', $value)) {
            return "SQL";
        }
        if ($queryOptions["save"]) {
            return (string)$queryOptions["function"][$name];
        }
        if ($update && preg_match('~^CURRENT_TIMESTAMP~i', $field->onUpdate)) {
            return 'now';
        }
        if ($value === null) {
            return 'NULL';
        }
        if ($value === false) {
            return null;
        }
        return '';
    }
}

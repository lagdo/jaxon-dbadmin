<?php

namespace Lagdo\DbAdmin\DbAdmin;

use Exception;

/**
 * Admin table query functions
 */
class TableQueryAdmin extends AbstractAdmin
{
    /**
     * Get data for an input field
     */
    protected function getFieldInput($field, $value, $function, $options)
    {
        $save = $options["save"];
        // From functions.inc.php (function input($field, $value, $function))
        $name = $this->util->html($this->util->bracketEscape($field->name));
        $entry = [
            'type' => $this->util->html($field->fullType),
            'name' => $name,
            'field' => [
                'type' => $field->type,
            ],
        ];

        if (\is_array($value) && !$function) {
            $value = \json_encode($args, JSON_PRETTY_PRINT);
            $function = "json";
        }
        $reset = ($this->driver->jush() == "mssql" && $field->autoIncrement);
        if ($reset && !$save) {
            $function = null;
        }
        $functions = [];
        if ($reset) {
            $functions["orig"] = $this->trans->lang('original');
        }
        $functions += $this->util->editFunctions($field);

        // Input for functions
        $has_function = (\in_array($function, $functions) || isset($functions[$function]));
        if ($field->type == "enum") {
            $entry['functions'] = [
                'type' => 'name',
                'name' => $this->util->html($functions[""] ?? ''),
            ];
        } elseif (\count($functions) > 1) {
            $entry['functions'] = [
                'type' => 'select',
                'name' => "function[$name]",
                'options' => $functions,
                'selected' => $function === null || $has_function ? $function : "",
            ];
        } else {
            $entry['functions'] = [
                'type' => 'name',
                'name' => $this->util->html(\reset($functions)),
            ];
        }

        // Input for value
        // The HTML code generated by Adminer is kept here.
        $attrs = " name='fields[$name]'";
        $entry['input'] = ['type' => ''];
        if ($field->type == "enum") {
            $entry['input']['type'] = 'radio';
            $entry['input']['value'] = $this->util->editInput(isset($options["select"]), $field, $attrs, $value);
        } elseif (\preg_match('~bool~', $field->type)) {
            $entry['input']['type'] = 'checkbox';
            $entry['input']['value'] = ["<input type='hidden'$attrs value='0'>" . "<input type='checkbox'" .
                (\preg_match('~^(1|t|true|y|yes|on)$~i', $value) ? " checked='checked'" : "") . "$attrs value='1'>"];
        } elseif ($field->type == "set") {
            $entry['input']['type'] = 'checkbox';
            $entry['input']['value'] = [];
            \preg_match_all("~'((?:[^']|'')*)'~", $field->length, $matches);
            foreach ($matches[1] as $i => $val) {
                $val = \stripcslashes(\str_replace("''", "'", $val));
                $checked = (\is_int($value) ? ($value >> $i) & 1 : \in_array($val, \explode(",", $value), true));
                $entry['input']['value'][] = "<label><input type='checkbox' name='fields[$name][$i]' value='" . (1 << $i) . "'" .
                    ($checked ? ' checked' : '') . ">" . $this->util->html($this->util->editValue($val, $field)) . '</label>';
            }
        } elseif (\preg_match('~blob|bytea|raw|file~', $field->type) && $this->util->iniBool("file_uploads")) {
            $entry['input']['value'] = "<input type='file' name='fields-$name'>";
        } elseif (($text = \preg_match('~text|lob|memo~i', $field->type)) || \preg_match("~\n~", $value)) {
            if ($text && $this->driver->jush() != "sqlite") {
                $attrs .= " cols='50' rows='12'";
            } else {
                $rows = \min(12, \substr_count($value, "\n") + 1);
                $attrs .= " cols='30' rows='$rows'" . ($rows == 1 ? " style='height: 1.2em;'" : ""); // 1.2em - line-height
            }
            $entry['input']['value'] = "<textarea$attrs>" . $this->util->html($value) . '</textarea>';
        } elseif ($function == "json" || \preg_match('~^jsonb?$~', $field->type)) {
            $entry['input']['value'] = "<textarea$attrs cols='50' rows='12' class='jush-js'>" .
                $this->util->html($value) . '</textarea>';
        } else {
            $unsigned = $field->unsigned ?? false;
            // int(3) is only a display hint
            $maxlength = (!\preg_match('~int~', $field->type) &&
                \preg_match('~^(\d+)(,(\d+))?$~', $field->length, $match) ?
                ((\preg_match("~binary~", $field->type) ? 2 : 1) * $match[1] + (($match[3] ?? null) ? 1 : 0) +
                (($match[2] ?? false) && !$unsigned ? 1 : 0)) :
                ($this->driver->typeExists($field->type) ? $this->driver->type($field->type) + ($unsigned ? 0 : 1) : 0));
            if ($this->driver->jush() == 'sql' && $this->driver->minVersion(5.6) && \preg_match('~time~', $field->type)) {
                $maxlength += 7; // microtime
            }
            // type='date' and type='time' display localized value which may be confusing,
            // type='datetime' uses 'T' as date and time separator
            $entry['input']['value'] = "<input" . ((!$has_function || $function === "") &&
                \preg_match('~(?<!o)int(?!er)~', $field->type) &&
                !\preg_match('~\[\]~', $field->fullType) ? " type='number'" : "") . " value='" .
                $this->util->html($value) . "'" . ($maxlength ? " data-maxlength='$maxlength'" : "") .
                (\preg_match('~char|binary~', $field->type) && $maxlength > 20 ? " size='40'" : "") . "$attrs>";
        }

        return $entry;
    }

    /**
     * Get the table fields
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    private function getFields(string $table, array $queryOptions)
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
     * Get data for insert/update on a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function getQueryData(string $table, array $queryOptions = [])
    {
        // Default options
        $queryOptions['clone'] = false;
        $queryOptions['save'] = false;

        list($fields, $where, $update) = $this->getFields($table, $queryOptions);

        // From edit.inc.php
        $row = null;
        if ($where) {
            $select = [];
            foreach ($fields as $name => $field) {
                if (isset($field->privileges["select"])) {
                    $as = $this->driver->convertField($field);
                    if ($queryOptions["clone"] && $field->autoIncrement) {
                        $as = "''";
                    }
                    if ($this->driver->jush() == "sql" && \preg_match("~enum|set~", $field->type)) {
                        $as = "1*" . $this->driver->escapeId($name);
                    }
                    $select[] = ($as ? "$as AS " : "") . $this->driver->escapeId($name);
                }
            }
            $row = [];
            if (!$this->driver->support("table")) {
                $select = ["*"];
            }
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

        if (!$this->driver->support("table") && empty($fields)) {
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
        }

        // From functions.inc.php (function edit_form($table, $fields, $row, $update))
        $entries = [];
        $tableName = $this->util->tableName($this->driver->tableStatusOrName($table, true));
        $error = null;
        if ($row === null) {
            $error = $this->trans->lang('No rows.');
        } elseif (!$fields) {
            $error = $this->trans->lang('You have no privileges to update this table.');
        } else {
            foreach ($fields as $name => $field) {
                // $default = $queryOptions["set"][$this->util->bracketEscape($name)] ?? null;
                // if($default === null)
                // {
                $default = $field->default;
                if ($field->type == "bit" && \preg_match("~^b'([01]*)'\$~", $default, $regs)) {
                    $default = $regs[1];
                }
                // }
                $value = (
                    $row !== null ? (
                        $row[$name] != "" && $this->driver->jush() == "sql" && \preg_match("~enum|set~", $field->type) ?
                        (\is_array($row[$name]) ? \array_sum($row[$name]) : +$row[$name]) :
                        (\is_bool($row[$name]) ? +$row[$name] : $row[$name])
                    ) : (
                        !$update && $field->autoIncrement ? "" : (isset($queryOptions["select"]) ? false : $default)
                    )
                );
                if (!$queryOptions["save"] && \is_string($value)) {
                    $value = $this->util->editValue($value, $field);
                }
                $function = (
                    $queryOptions["save"]
                    ? (string)$queryOptions["function"][$name]
                    : (
                        $update && \preg_match('~^CURRENT_TIMESTAMP~i', $field->onUpdate)
                        ? "now"
                        : ($value === false ? null : ($value !== null ? '' : 'NULL'))
                    )
                );
                if (!$update && $value == $field->default && \preg_match('~^[\w.]+\(~', $value)) {
                    $function = "SQL";
                }
                if (\preg_match("~time~", $field->type) && \preg_match('~^CURRENT_TIMESTAMP~i', $value)) {
                    $value = "";
                    $function = "now";
                }

                $entries[$name] = $this->getFieldInput($field, $value, $function, $queryOptions);
            }
        }

        $mainActions = [
            'query-save' => $this->trans->lang('Save'),
            'query-cancel' => $this->trans->lang('Cancel'),
        ];

        $fields = $entries;
        return \compact('mainActions', 'tableName', 'error', 'fields');
    }

    /**
     * Insert a new item in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function insertItem(string $table, array $queryOptions)
    {
        list($fields, $where, $update) = $this->getFields($table, $queryOptions);

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

        return \compact('result', 'message', 'error');
    }

    /**
     * Update one or more items in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function updateItem(string $table, array $queryOptions)
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

        return \compact('result', 'message', 'error');
    }

    /**
     * Delete one or more items in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function deleteItem(string $table, array $queryOptions)
    {
        list($fields, $where, $update) = $this->getFields($table, $queryOptions);

        // From edit.inc.php
        $indexes = $this->driver->indexes($table);
        $uniqueIds = $this->util->uniqueIds($queryOptions["where"], $indexes);
        $queryWhere = "\nWHERE $where";

        $result = $this->driver->delete($table, $queryWhere, count($uniqueIds));
        $message = $this->trans->lang('Item has been deleted.');

        $error = $this->driver->error();

        return \compact('result', 'message', 'error');
    }
}

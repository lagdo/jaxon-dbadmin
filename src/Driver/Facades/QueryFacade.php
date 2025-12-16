<?php

namespace Lagdo\DbAdmin\Db\Driver\Facades;

use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function array_sum;
use function count;
use function file_get_contents;
use function function_exists;
use function iconv;
use function implode;
use function is_array;
use function is_bool;
use function is_string;
use function json_decode;
use function preg_match;
use function stripos;
use function substr;

/**
 * Facade to table query functions
 */
class QueryFacade extends AbstractFacade
{
    use Traits\InputFieldTrait;
    use Traits\QueryInputTrait;

    /**
     * @var bool
     */
    private bool $isUpdate;

    /**
     * @param TableFieldEntity $field
     * @param string $name
     * @param array|null $row
     * @param array $options
     *
     * @return mixed
     */
    private function getRowFieldValue(TableFieldEntity $field, string $name,
        ?array $row, array $options): mixed
    {
        // $default = $options["set"][$this->driver->bracketEscape($name)] ?? null;
        // if($default === null)
        // {
        $default = $field->default;
        if ($field->type == "bit" && preg_match("~^b'([01]*)'\$~", $default, $regs)) {
            $default = $regs[1];
        }
        // }
        return match(true) {
            $row === null || !isset($row[$name]) => !$this->isUpdate && $field->autoIncrement ?
                "" : (isset($options["select"]) ? false : $default),
            $row[$name] != "" && $this->driver->jush() == "sql" &&
                preg_match("~enum|set~", $field->type) => is_array($row[$name]) ?
                    array_sum($row[$name]) : +$row[$name],
            default => is_bool($row[$name]) ? +$row[$name] : $row[$name],
        };
    }

    /**
     * @param TableFieldEntity $field
     * @param string $name
     * @param mixed $value
     * @param array $options
     *
     * @return string|null
     */
    private function getRowFieldFunction(TableFieldEntity $field, string $name, $value,
        array $options): ?string
    {
        return match(true) {
            !$this->isUpdate && $value == $field->default &&
                preg_match('~^[\w.]+\(~', $value ?? '') => "SQL",
            isset($options["save"]) && isset($options["function"]) =>
                (string)$options["function"][$name],
            $this->isUpdate && preg_match('~^CURRENT_TIMESTAMP~i', $field->onUpdate) => 'now',
            $value === null => 'NULL',
            $value === false => null,
            default => '',
        };
    }

    /**
     * @param array $names
     * @param array $functions
     * @param bool $addSql
     * @param TableFieldEntity $field
     *
     * @return array
     */
    private function addEditFunctions(array $names, array $functions, TableFieldEntity $field): array
    {
        foreach ($functions as $pattern => $_functions) {
            if (!$pattern || preg_match("~$pattern~", $field->type)) {
                $names = [...$names, ...$_functions]; // Array merge
            }
        }
        return $names;
    }

    /**
     * Functions displayed in edit form
     * function editFunctions() in adminer.inc.php
     *
     * @param TableFieldEntity $field Single field from fields()
     *
     * @return array
     */
    private function editFunctions(TableFieldEntity $field): array
    {
        if ($field->autoIncrement && !$this->isUpdate) {
            return [$this->utils->trans->lang('Auto Increment')];
        }

        $names = $field->null ? ['NULL', ''] : [''];
        $functions = $this->driver->insertFunctions();
        $names = $this->addEditFunctions($names, $functions, $field);

        $functions = $this->driver->editFunctions();
        if (/*!isset($this->utils->input->values['call']) &&*/ $this->isUpdate) { // relative functions
            $names = $this->addEditFunctions($names, $functions, $field);
        }
        $structuredTypes = $this->driver->structuredTypes();
        $userTypes = $structuredTypes[$this->utils->trans->lang('User types')] ?? [];
        if ($functions && !preg_match('~set|bool~', $field->type) &&
            !$this->utils->isBlob($field, $userTypes)) {
            $names[] = 'SQL';
        }

        // $dbFunctions = [
        //     'insert' => $this->driver->insertFunctions(),
        //     'edit' => $this->driver->editFunctions(),
        // ];
        // foreach ($dbFunctions as $key => $functions) {
        //     if ($key === 'insert' || (!$isCall && $this->isUpdate)) { // relative functions
        //         foreach ($functions as $pattern => $value) {
        //             if (!$pattern || preg_match("~$pattern~", $field->type)) {
        //                 $names = [...$names, ...$value]; // Array merge
        //             }
        //         }
        //     }
        //     if ($key === 'edit' && !preg_match('~set|bool~', $field->type) && !$this->utils->isBlob($field, $userTypes)) {
        //         $names[] = 'SQL';
        //     }
        // }

        return $names;
    }

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
        $name = $this->utils->str->html($this->driver->bracketEscape($field->name));
        $save = $options['save'] ?? '';
        $reset = $this->driver->jush() === 'mssql' && $field->autoIncrement;
        if (is_array($value) && !$function) {
            $value = json_encode($value, JSON_PRETTY_PRINT);
            $function = 'json';
        }
        if ($reset && !$save) {
            $function = null;
        }
        $functions = [];
        if ($reset) {
            $functions['orig'] = $this->utils->trans->lang('original');
        }
        $functions += $this->editFunctions($field);
        return [
            'type' => $this->utils->str->html($field->fullType),
            'name' => $name,
            'field' => [
                'type' => $field->type,
            ],
            'functions' => $this->getEntryFunctions($field, $name, $function, $functions),
            'input' => $this->getEntryInput($field, $name, $value, $function, $functions, $options),
        ];
    }

    /**
     * Get the table fields
     *
     * @param string $table         The table name
     * @param array  $options       The query options
     * @param string $privilege
     *
     * @return array
     */
    private function getFields(string $table, array $options, string $privilege): array
    {
        // From edit.inc.php
        $fields = $this->driver->fields($table);
        $where = $this->driver->where($options, $fields);
        // Remove fields without the required privilege, or that cannot be edited.
        $fields = array_filter($fields, fn(TableFieldEntity $field) =>
            isset($field->privileges[$privilege]) &&
            $this->page->fieldName($field) !== '' &&
            !$field->generated);

        return [$fields, $where];
    }

    /**
     * @param array $fields
     * @param array|null $row
     * @param array $options
     *
     * @return array
     */
    private function getQueryEntries(array $fields, $row, array $options): array
    {
        // From functions.inc.php (function edit_form($table, $fields, $row, $update))
        $entries = [];
        foreach ($fields as $name => $field) {
            $value = $this->getRowFieldValue($field, $name, $row, $options);
            $function = $this->getRowFieldFunction($field, $name, $value, $options);
            if (preg_match('~time~', $field->type) && is_string($value) &&
                preg_match('~^CURRENT_TIMESTAMP~i', $value)) {
                $value = '';
                $function = 'now';
            }
            $entries[$name] = $this->getFieldInput($field, $value, $function, $options);
        }
        return $entries;
    }

    /**
     * @param array $fields
     * @param array $options
     *
     * @return array
     */
    private function getSelectClauses(array $fields, array $options): array
    {
        if (!$this->driver->support("table")) {
            return ["*"];
        }

        $select = [];
        foreach ($fields as $name => $field) {
            if (isset($field->privileges["select"])) {
                $as = $options["clone"] && $field->autoIncrement ? "''" :
                    $this->driver->convertField($field);
                $select[] = ($as ? "$as AS " : "") . $this->driver->escapeId($name);
            }
        }
        return $select;
    }

    /**
     * @param string $table
     * @param string $where
     * @param array $fields
     * @param array $options
     *
     * @return array|false|null
     */
    private function getUpdateQueryData(string $table, string $where,
        array $fields, array $options): array|false|null
    {
        // From edit.inc.php
        $select = $this->getSelectClauses($fields, $options);
        if (!$select) {
            return false; // No data
        }

        $row = [];
        $statement = $this->driver->select($table, $select, [$where],
            $select, [], isset($options["select"]) ? 2 : 1);
        if (!$statement) {
            return null; // Error
        }
        $row = $statement->fetchAssoc();
        // if(isset($options["select"]) && (!$row || $statement->fetchAssoc()))
        if(!$row || $statement->fetchAssoc())
        {
            // $statement->rowCount() != 1 isn't available in all drivers
            return false; // No data
        }

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

        return $row;
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
        $this->isUpdate = false;

        $tableName = $this->page->tableName($this->driver->tableStatusOrName($table, true));
        [$fields,] = $this->getFields($table, $options, 'insert');
        if (empty($fields)) {
            return [
                'tableName' => $tableName,
                'error' => $this->utils->trans->lang('You have no privileges to update this table.'),
            ];
        }

        // No data when inserting a new row
        $rowData = [];
        return [
            'tableName' => $tableName,
            'error' => null,
            'fields' =>  $this->getQueryEntries($fields, $rowData, $options),
        ];
    }

    /**
     * Get data for update/delete in a table
     *
     * @param string $table         The table name
     * @param array  $options       The query options
     *
     * @return array
     */
    public function getUpdateData(string $table, array $options = []): array
    {
        $this->isUpdate = true;
        // Default options
        $options['clone'] = false;
        $options['save'] = false;

        $tableName = $this->page->tableName($this->driver->tableStatusOrName($table, true));
        [$fields, $where] = $this->getFields($table, $options, 'update');
        if (empty($fields)) {
            return [
                'tableName' => $tableName,
                'error' => $this->utils->trans->lang('You have no privileges to update this table.'),
            ];
        }

        $rowData = !$where ? false :
            $this->getUpdateQueryData($table, $where, $fields, $options);
        if (!$rowData) {
            return [
                'tableName' => $tableName,
                'error' => $rowData === null ? $this->driver->error() :
                    $this->utils->trans->lang('You have no privileges to update this table.'),
            ];
        }

        return [
            'tableName' => $tableName,
            'error' => null,
            'fields' =>  $this->getQueryEntries($fields, $rowData, $options),
        ];
    }

    /**
     * @param array $file
     * @param string $key
     * @param bool $decompress
     *
     * @return string
     */
    private function readFileContent(array $file, string $key, bool $decompress): string
    {
        $name = $file['name'][$key];
        $tmpName = $file['tmp_name'][$key];
        $content = file_get_contents($decompress && preg_match('~\.gz$~', $name) ?
            "compress.zlib://$tmpName" : $tmpName); //! may not be reachable because of open_basedir
        if (!$decompress) {
            return $content;
        }
        $start = substr($content, 0, 3);
        if (function_exists('iconv') && preg_match("~^\xFE\xFF|^\xFF\xFE~", $start, $regs)) {
            // not ternary operator to save memory
            return iconv('utf-16', 'utf-8', $content) . "\n\n";
        }
        if ($start == "\xEF\xBB\xBF") { // UTF-8 BOM
            return substr($content, 3) . "\n\n";
        }
        return $content;
    }

    /**
     * Get file contents from $_FILES
     *
     * @param string $key
     * @param bool $decompress
     *
     * @return string|null
     */
    private function getFileContents(string $key, bool $decompress = false)
    {
        $file = $_FILES[$key];
        if (!$file) {
            return null;
        }
        foreach ($file as $key => $val) {
            $file[$key] = (array) $val;
        }
        $queries = '';
        foreach ($file['error'] as $key => $error) {
            if (($error)) {
                return $error;
            }
            $queries .= $this->readFileContent($file, $key, $decompress);
        }
        //! Support SQL files not ending with semicolon
        return $queries;
    }

    /**
     * Process edit input field
     *
     * @param TableFieldEntity $field
     * @param array $values
     * @param array $enumValues
     *
     * @return mixed
     */
    private function processInput(TableFieldEntity $field, array $values, array $enumValues): mixed
    {
        if (stripos($field->default, "GENERATED ALWAYS AS ") === 0) {
            return false;
        }

        $idf = $this->driver->bracketEscape($field->name);
        $function = $values['function'][$idf];
        $value = $values['fields'][$idf];

        if ($field->type === "enum" || count($enumValues) > 0) {
            $value = $value[0];
            if ($value === "orig") {
                return false;
            }
            if ($value === "null") {
                return "NULL";
            }
            $value = substr($value, 4); // 4 - strlen("val-")
        }

        if ($field->autoIncrement && $value === '') {
            return null;
        }
        if ($function === 'orig') {
            return preg_match('~^CURRENT_TIMESTAMP~i', $field->onUpdate) ?
                $this->driver->escapeId($field->name) : false;
        }

        if ($field->type === 'set') {
            $value = implode(',', (array)$value);
        }

        if ($function === 'json') {
            $function = '';
            $value = json_decode($value, true);
            //! report errors
            return !is_array($value) ? false : $value;
        }
        if ($this->utils->isBlob($field) && $this->utils->iniBool('file_uploads')) {
            $file = $this->getFileContents("fields-$idf");
            //! report errors
            return !is_string($file) ? false : $this->driver->quoteBinary($file);
        }
        return $this->getUnconvertedFieldValue($field, $value, $function);
    }

    /**
     * @param array $fields
     * @param array $inputs
     *
     * @return array
     */
    private function getInputValues(array $fields, array $inputs): array
    {
        $userTypes = $this->driver->userTypes(true);
        // From edit.inc.php
        $values = [];
        foreach ($fields as $name => $field) {
            $userType = $userTypes[$field->type] ?? null;
            $enumValues = !$userType ? [] : $userType->enums;
            $value = $this->processInput($field, $inputs, $enumValues);
            if ($value !== false && $value !== null) {
                $values[$this->driver->escapeId($name)] = $value;
            }
        }
        return $values;
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
        $this->isUpdate = false;

        [$fields,] = $this->getFields($table, $options, 'insert');
        $values = $this->getInputValues($fields, $values);

        $result = $this->driver->insert($table, $values);
        $lastId = !$result ? 0 : $this->driver->lastAutoIncrementId();

        return [
            'result' => $result,
            'message' => $this->utils->trans->lang('Item%s has been inserted.',
                $lastId ? " $lastId" : ''),
            'error' => $this->driver->error(),
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
        $this->isUpdate = true;

        [$fields, $where] = $this->getFields($table, $options, 'update');
        $values = $this->getInputValues($fields, $values);

        // From edit.inc.php
        $indexes = $this->driver->indexes($table);
        $uniqueIds = $this->utils->uniqueIds($options['where'], $indexes);
        $limit = count($uniqueIds) === 0 ? 1 : 0; // Limit to 1 if no unique ids are found.

        if (!$this->driver->update($table, $values, "\nWHERE $where", $limit)) {
            return [
                'error' => $this->driver->error(),
            ];
        }

        // Get the modified data
        // Todo: check if the values in the where clause are changed.
        $statement = $this->driver->select($table, array_keys($values), [$where]);
        return [
            'data' => !$statement ? null : $statement->fetchAssoc(),
            'message' => $this->utils->trans->lang('Item has been updated.'),
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
        $this->isUpdate = true;

        [, $where] = $this->getFields($table, $options, 'update');

        // From edit.inc.php
        $indexes = $this->driver->indexes($table);
        $uniqueIds = $this->utils->uniqueIds($options['where'], $indexes);
        $limit = count($uniqueIds) === 0 ? 1 : 0; // Limit to 1 if no unique ids are found.

        $result = $this->driver->delete($table, "\nWHERE $where", $limit);

        return [
            'result' => $result,
            'message' => $this->utils->trans->lang('Item has been deleted.'),
            'error' => $this->driver->error(),
        ];
    }
}

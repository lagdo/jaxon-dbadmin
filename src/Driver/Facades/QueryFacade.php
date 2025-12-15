<?php

namespace Lagdo\DbAdmin\Db\Driver\Facades;

use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function array_sum;
use function file_get_contents;
use function function_exists;
use function iconv;
use function is_array;
use function is_bool;
use function is_string;
use function json_decode;
use function preg_match;
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
     * @param array $queryOptions
     *
     * @return mixed
     */
    private function getRowFieldValue(TableFieldEntity $field, string $name,
        ?array $row, array $queryOptions): mixed
    {
        // $default = $queryOptions["set"][$this->driver->bracketEscape($name)] ?? null;
        // if($default === null)
        // {
        $default = $field->default;
        if ($field->type == "bit" && preg_match("~^b'([01]*)'\$~", $default, $regs)) {
            $default = $regs[1];
        }
        // }
        return match(true) {
            $row === null || !isset($row[$name]) => !$this->isUpdate && $field->autoIncrement ?
                "" : (isset($queryOptions["select"]) ? false : $default),
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
     * @param array $queryOptions
     *
     * @return string|null
     */
    private function getRowFieldFunction(TableFieldEntity $field, string $name, $value,
        array $queryOptions): ?string
    {
        return match(true) {
            !$this->isUpdate && $value == $field->default &&
                preg_match('~^[\w.]+\(~', $value ?? '') => "SQL",
            isset($queryOptions["save"]) && isset($queryOptions["function"]) =>
                (string)$queryOptions["function"][$name],
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
     * @param array  $queryOptions  The query options
     * @param string $privilege
     *
     * @return array
     */
    private function getFields(string $table, array $queryOptions, string $privilege): array
    {
        // From edit.inc.php
        $fields = $this->driver->fields($table);
        $where = $this->driver->where($queryOptions, $fields);
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
     * @param array $queryOptions
     *
     * @return array
     */
    private function getQueryEntries(array $fields, $row, array $queryOptions): array
    {
        // From functions.inc.php (function edit_form($table, $fields, $row, $update))
        $entries = [];
        foreach ($fields as $name => $field) {
            $value = $this->getRowFieldValue($field, $name, $row, $queryOptions);
            $function = $this->getRowFieldFunction($field, $name, $value, $queryOptions);
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
     * @param array $fields
     * @param array $queryOptions
     *
     * @return array
     */
    private function getSelectClauses(array $fields, array $queryOptions): array
    {
        if (!$this->driver->support("table")) {
            return ["*"];
        }

        $select = [];
        foreach ($fields as $name => $field) {
            if (isset($field->privileges["select"])) {
                $as = $queryOptions["clone"] && $field->autoIncrement ? "''" :
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
     * @param array $queryOptions
     *
     * @return array|false|null
     */
    private function getUpdateQueryData(string $table, string $where,
        array $fields, array $queryOptions): array|false|null
    {
        // From edit.inc.php
        $select = $this->getSelectClauses($fields, $queryOptions);
        if (!$select) {
            return false; // No data
        }

        $row = [];
        $statement = $this->driver->select($table, $select, [$where],
            $select, [], (isset($queryOptions["select"]) ? 2 : 1));
        if (!$statement) {
            return null; // Error
        }
        $row = $statement->fetchAssoc();
        // if(isset($queryOptions["select"]) && (!$row || $statement->fetchAssoc()))
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
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function getInsertData(string $table, array $queryOptions = []): array
    {
        $this->isUpdate = false;

        $tableName = $this->page->tableName($this->driver->tableStatusOrName($table, true));
        [$fields,] = $this->getFields($table, $queryOptions, 'insert');
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
            'fields' =>  $this->getQueryEntries($fields, $rowData, $queryOptions),
        ];
    }

    /**
     * Get data for update/delete in a table
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function getUpdateData(string $table, array $queryOptions = []): array
    {
        $this->isUpdate = true;
        // Default options
        $queryOptions['clone'] = false;
        $queryOptions['save'] = false;

        $tableName = $this->page->tableName($this->driver->tableStatusOrName($table, true));
        [$fields, $where] = $this->getFields($table, $queryOptions, 'update');
        if (empty($fields)) {
            return [
                'tableName' => $tableName,
                'error' => $this->utils->trans->lang('You have no privileges to update this table.'),
            ];
        }

        $rowData = !$where ? false :
            $this->getUpdateQueryData($table, $where, $fields, $queryOptions);
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
            'fields' =>  $this->getQueryEntries($fields, $rowData, $queryOptions),
        ];
    }

    /**
     * @param mixed $value
     *
     * @return false|int|string
     */
    private function getEnumFieldValue($value)
    {
        return match(true) {
            $value === -1 => false,
            $value === '' => 'NULL',
            default => +$value,
        };
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return string|false
     */
    private function getOrigFieldValue(TableFieldEntity $field)
    {
        return preg_match('~^CURRENT_TIMESTAMP~i', $field->onUpdate) === false ?
            false : $this->driver->escapeId($field->name);
    }

    /**
     * @param mixed $value
     *
     * @return array|false
     */
    private function getJsonFieldValue($value)
    {
        //! Report errors
        return !is_array($value = json_decode($value, true)) ? false : $value;
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
     * @param TableFieldEntity $field
     *
     * @return string|false
     */
    private function getBinaryFieldValue(TableFieldEntity $field)
    {
        if (!$this->utils->iniBool('file_uploads')) {
            return false;
        }

        $idf = $this->driver->bracketEscape($field->name);
        $file = $this->getFileContents("fields-$idf");
        //! report errors
        return !is_string($file) ? false : $this->driver->quoteBinary($file);
    }

    /**
     * Process edit input field
     *
     * @param TableFieldEntity $field
     * @param array $inputs The user inputs
     *
     * @return array|false|float|int|string|null
     */
    private function processInput(TableFieldEntity $field, array $inputs)
    {
        $idf = $this->driver->bracketEscape($field->name);
        $function = $inputs['function'][$idf] ?? '';
        $value = $inputs['fields'][$idf];

        return match(true) {
            $field->autoIncrement && $value === '' => null,
            $function === 'NULL' => 'NULL',
            $field->type === 'enum' => $this->getEnumFieldValue($value),
            $function === 'orig' => $this->getOrigFieldValue($field),
            $field->type === 'set' => array_sum((array) $value),
            $function == 'json' => $this->getJsonFieldValue($value),
            preg_match('~blob|bytea|raw|file~', $field->type) =>
                $this->getBinaryFieldValue($field),
            default => $this->getUnconvertedFieldValue($field, $value, $function),
        };
    }

    private function getInputValues(array $fields, array $queryOptions, array $values): array
    {
        // From edit.inc.php
        $values = [];
        foreach ($fields as $name => $field) {
            $value = $this->processInput($field, $values);
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
     * @param array  $queryOptions  The query options
     * @param array  $values        The updated values
     *
     * @return array
     */
    public function insertItem(string $table, array $queryOptions, array $values): array
    {
        $this->isUpdate = false;

        [$fields,] = $this->getFields($table, $queryOptions, 'insert');
        $values = $this->getInputValues($fields, $queryOptions, $values);

        $result = $this->driver->insert($table, $values);
        $lastId = $result ? $this->driver->lastAutoIncrementId() : 0;

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
     * @param array  $queryOptions  The query options
     * @param array  $values        The updated values
     *
     * @return array
     */
    public function updateItem(string $table, array $queryOptions, array $values): array
    {
        $this->isUpdate = true;

        [$fields, $where] = $this->getFields($table, $queryOptions, 'update');
        $values = $this->getInputValues($fields, $queryOptions, $values);

        // From edit.inc.php
        $indexes = $this->driver->indexes($table);
        $uniqueIds = $this->utils->uniqueIds($queryOptions['where'], $indexes);
        $limit = !$uniqueIds ? 1 : 0; // Limit to 1 if no unique ids are found.

        $result = $this->driver->update($table, $values, "\nWHERE $where", $limit);

        return [
            'result' => $result,
            'message' => $this->utils->trans->lang('Item has been updated.'),
            'error' => $this->driver->error(),
        ];
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
        $this->isUpdate = true;

        [, $where] = $this->getFields($table, $queryOptions, 'update');

        // From edit.inc.php
        $indexes = $this->driver->indexes($table);
        $uniqueIds = $this->utils->uniqueIds($queryOptions['where'], $indexes);
        $limit = !$uniqueIds ? 1 : 0; // Limit to 1 if no unique ids are found.

        $result = $this->driver->delete($table, "\nWHERE $where", $limit);

        return [
            'result' => $result,
            'message' => $this->utils->trans->lang('Item has been deleted.'),
            'error' => $this->driver->error(),
        ];
    }
}

<?php

namespace Lagdo\DbAdmin\Db\Page\Dml;

use Lagdo\DbAdmin\Db\Page\AppPage;
use Lagdo\DbAdmin\Driver\Utils\Utils;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;
use Lagdo\Facades\Logger;

use function array_pad;
use function bin2hex;
use function count;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function min;
use function preg_match;
use function preg_match_all;
use function reset;
use function stripcslashes;
use function str_replace;
use function substr_count;

/**
 * Writes data for the user forms.
 */
class DataRowWriter
{
    private bool $isUpdate = false;

    /**
     * The constructor
     *
     * @param AppPage $page
     * @param DriverInterface $driver
     * @param Utils $utils
     * @param string $action
     * @param string $operation
     */
    public function __construct(private AppPage $page, private DriverInterface $driver,
        private Utils $utils, private string $action, private string $operation)
    {
        $this->isUpdate = $operation === 'update';
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
     * @param TableFieldEntity $field
     * @param string $name
     * @param string|null $function
     * @param array $functions
     *
     * @return array
     */
    private function getEntryFunctions(TableFieldEntity $field, string $name, ?string $function, array $functions): array
    {
        // Input for functions
        if ($field->type === 'enum') {
            return [
                'type' => 'name',
                'name' => $this->utils->str->html($functions[''] ?? ''),
            ];
        }
        if (count($functions) <= 1) {
            return [
                'type' => 'name',
                'name' => $this->utils->str->html(reset($functions)),
            ];
        }
        $hasFunction = in_array($function, $functions) || isset($functions[$function]);
        return [
            'type' => 'select',
            'name' => "function[$name]",
            'options' => $functions,
            'value' => $function === null || $hasFunction ? $function : '',
        ];
    }

    /**
     * @param string $val
     * @param int $i
     * @param mixed $value
     *
     * @return array
     */
    private function getEnumItemValue(string $val, int $i, $value): array
    {
        $val = stripcslashes(str_replace("''", "'", $val));
        $checked = (is_int($value) ? $value == $i + 1 :
            (is_array($value) ? in_array($i+1, $value) : $value === $val));
        return ['value' => $i + 1, 'checked' => $checked, 'text' => $this->utils->str->html($val)];
    }

    /**
     * @param TableFieldEntity $field
     * @param bool $select
     * @param mixed $value
     *
     * @return array
     */
    private function getEnumValues(TableFieldEntity $field, bool $select, $value): array
    {
        $values = [];
        if (($select)) {
            $values[] = ['value' => '-1', 'checked' => true,
                'text' => '<i>' . $this->utils->trans->lang('original') . '</i>'];
        }
        if ($field->null) {
            $values[] =  ['value' => '', 'checked' => $value === null && !$select, 'text' => '<i>NULL</i>'];
        }

        // From functions.inc.php (function enum_input())
        $empty = 0; // 0 - empty
        $values[] = ['value' => $empty, 'checked' => is_array($value) ? in_array($empty, $value) : $value === 0,
            'text' => '<i>' . $this->utils->trans->lang('empty') . '</i>'];

        preg_match_all("~'((?:[^']|'')*)'~", $field->fullType, $matches);
        foreach ($matches[1] as $i => $val) {
            $values[] = $this->getEnumItemValue($val, $i, $value);
        }

        return $values;
    }

    /**
     * @param TableFieldEntity $field
     * @param array $attrs
     * @param mixed $value
     *
     * @return array
     */
    private function getSetInput(TableFieldEntity $field, array $attrs, $value): array
    {
        $values = [];
        preg_match_all("~'((?:[^']|'')*)'~", $field->length, $matches);
        foreach ($matches[1] as $i => $val) {
            $val = stripcslashes(str_replace("''", "'", $val));
            $checked = (is_int($value) ? ($value >> $i) & 1 : in_array($val, explode(',', $value), true));
            $values[] = ['value=' => (1 << $i), 'checked' => $checked, 'text' => $this->utils->str->html($val)];
        }
        return ['type' => 'checkbox', 'attrs' => $attrs, 'values' => $values];
    }

    /**
     * @param TableFieldEntity $field
     * @param array $attrs
     * @param mixed $value
     *
     * @return array
     */
    private function getBlobInput(TableFieldEntity $field, array $attrs, $value): array
    {
        if (preg_match('~text|lob|memo~i', $field->type) && $this->driver->jush() !== 'sqlite') {
            $attrs['cols'] = 50;
            $attrs['rows'] = 12;
        } else {
            $rows = min(12, substr_count($value, "\n") + 1);
            $attrs['cols'] = 30;
            $attrs['rows'] = $rows;
            if ($rows == 1) {
                $attrs['style'] = 'height: 1.2em;';
            }
        }
        return ['type' => 'textarea', 'attrs' => $attrs, 'value' => $this->utils->str->html($value)];
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return int
     */
    private function getLengthValue(TableFieldEntity $field): int
    {
        // int(3) is only a display hint
        if (!preg_match('~int~', $field->type) &&
            preg_match('~^(\d+)(,(\d+))?$~', $field->length, $match)) {
            $match = array_pad($match, 4, false);
            $length1 = preg_match('~binary~', $field->type) ? 2 : 1;
            $length2 = empty($match[3]) ? 0 : 1;
            $length3 = empty($match[2]) || $field->unsigned ? 0 : 1;
            return $length1 * $match[1] + $length2 + $length3;
        }
        return $this->driver->typeLength($field);
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return int
     */
    private function getMaxLength(TableFieldEntity $field): int
    {
        $maxlength = $this->getLengthValue($field);
        if ($this->driver->jush() == 'sql' && $this->driver->minVersion(5.6) &&
            preg_match('~time~', $field->type)) {
            return $maxlength + 7; // microtime
        }
        return $maxlength;
    }

    /**
     * @param TableFieldEntity $field
     * @param array $attrs
     *
     * @return void
     */
    private function setDataLength(TableFieldEntity $field, array &$attrs)
    {
        $maxlength = $this->getMaxLength($field);
        if ($maxlength > 0) {
            $attrs['data-maxlength'] = $maxlength;
        }
        if (preg_match('~char|binary~', $field->type) && $maxlength > 20) {
            $attrs['size'] = 40;
        }
    }

    /**
     * @param TableFieldEntity $field
     * @param array $attrs
     *
     * @return void
     */
    private function setDataType(TableFieldEntity $field, array &$attrs)
    {
        // type='date' and type='time' display localized value which may be confusing,
        // type='datetime' uses 'T' as date and time separator
        if (preg_match('~(?<!o)int(?!er)~', $field->type) && !preg_match('~\[\]~', $field->fullType)) {
            $attrs['type'] = 'number';
        }
    }

    /**
     * @param TableFieldEntity $field
     * @param array $attrs
     * @param mixed $value
     * @param string|null $function
     * @param array $functions
     *
     * @return array
     */
    private function getDefaultInput(TableFieldEntity $field, array $attrs, $value, $function, array $functions): array
    {
        $this->setDataLength($field, $attrs);
        $hasFunction = (in_array($function, $functions) || isset($functions[$function]));
        if (!$hasFunction || $function === '') {
            $this->setDataType($field, $attrs);
        }
        $attrs['value'] = $this->utils->str->html($value);
        return ['type' => 'input', 'attrs' => $attrs];
    }

    /**
     * @param TableFieldEntity $field
     * @param string $name
     * @param mixed $value
     * @param string|null $function
     * @param array $functions
     * @param array $options
     *
     * @return array
     */
    private function getEntryInput(TableFieldEntity $field, string $name, $value,
        ?string $function, array $functions, array $options): array
    {
        $attrs = ['name' => "fields[$name]"];
        if ($field->type === 'enum') {
            return ['type' => 'radio', 'attrs' => $attrs,
                'values' => $this->getEnumValues($field, isset($options['select']), $value)];
        }
        if (preg_match('~bool~', $field->type)) {
            $attrs['checked'] = preg_match('~^(1|t|true|y|yes|on)$~i', $value);
            return ['type' => 'bool', 'attrs' => $attrs];
        }
        if ($field->type === 'set') {
            return $this->getSetInput($field, $attrs, $value);
        }
        if (preg_match('~blob|bytea|raw|file~', $field->type) &&
            $this->utils->iniBool('file_uploads')) {
            $attrs['name'] = "fields-$name";
            return ['type' => 'file', 'attrs' => $attrs];
        }
        if (preg_match('~text|lob|memo~i', $field->type) || preg_match("~\n~", $value ?? '')) {
            return $this->getBlobInput($field, $attrs, $value);
        }
        if ($function === 'json' || preg_match('~^jsonb?$~', $field->type)) {
            $attrs['cols'] = 50;
            $attrs['rows'] = 12;
            return ['type' => 'textarea', 'attrs' => $attrs, 'value' => $this->utils->str->html($value)];
        }
        return $this->getDefaultInput($field, $attrs, $value, $function, $functions);
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
        // From html.inc.php (function input(array $field, $value, ?string $function, ?bool $autofocus = false))
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
            'function' => $this->getEntryFunctions($field, $name, $function, $functions),
            'input' => $this->getEntryInput($field, $name, $value, $function, $functions, $options),
        ];
    }

    /**
     * @param array $entries
     * @param array $options
     *
     * @return array
     */
    public function getRowDataFormInputs(array $entries, array $options): array
    {
        // From html.inc.php (function edit_form(string $table, array $fields, $row, ?bool $update, string $error = ''))
        $_entries = [];
        foreach ($entries as $name => [$field, $row, $value, $function, $autofocus]) {
            if (preg_match('~time~', $field->type) && is_string($value) &&
                preg_match('~^CURRENT_TIMESTAMP~i', $value)) {
                $value = '';
                $function = 'now';
            }
            $_entries[$name] = $this->getFieldInput($field, $value, $function, $options);
        }
        Logger::debug('Row data inputs', ['entries' => $_entries]);
        return $_entries;
    }













    /**
     * @param TableFieldEntity $field
     * @param array|null $rowData
     *
     * @return mixed
     */
    private function getFormFieldDefaultValue(TableFieldEntity $field, array|null $rowData): mixed
    {
        $update = $this->operation === 'update';
        // $default = $options["set"][$this->driver->bracketEscape($name)] ?? null;
        /*if ($default === null)*/ {
            $default = $field->default;
            if ($field->type == "bit" && preg_match("~^b'([01]*)'\$~", $default, $regs)) {
                $default = $regs[1];
            }
            if ($this->driver->jush() == "sql" && preg_match('~binary~', $field->type)) {
                $default = bin2hex($default); // same as UNHEX
            }
        }

        if ($rowData === null) {
            return match(true) {
                !$update && $field->autoIncrement => '',
                $this->action === 'select' => false,
                default => $default,
            };
        }

        $fieldValue = $rowData[$field->name];
        return match(true) {
            $fieldValue !== '' && $this->driver->jush() === 'sql' &&
                preg_match("~enum|set~", $field->type) &&
                is_array($fieldValue) => implode(",", $fieldValue),
            is_bool($fieldValue) => +$fieldValue,
            default => $fieldValue,
        };
    }

    /**
     * @param TableFieldEntity $field
     * @param array|null $rowData
     *
     * @return array
     */
    private function getInputValue(TableFieldEntity $field, array|null $rowData): array
    {
        $value = $this->getFormFieldDefaultValue($field, $rowData);
        // if (!$this->action !== 'save' && is_string($value)) {
        //     $value = adminer()->editVal($value, $field);
        // }

        $formInput = []; // No user imput available here.
        $update = $this->operation === 'update';
        $function = match(true) {
            $this->action === 'save' => $formInput['function'][$field->name] ?? '',
            $update && preg_match('~^CURRENT_TIMESTAMP~i', $field->onUpdate) => 'now',
            $value === false => null,
            $value !== null => '',
            default => 'NULL',
        };
        if ($this->action !== 'save' && !$update && $value === $field->default &&
            preg_match('~^[\w.]+\(~', $value)) {
            $function = 'SQL';
        }
        if (preg_match('~time~', $field->type) &&
            preg_match('~^CURRENT_TIMESTAMP~i', $value)) {
            $value = "";
            $function = "now";
        }
        if ($field->type == "uuid" && $value == "uuid()") {
            $value = "";
            $function = "uuid";
        }
        return [$value, $function];
    }

    /**
     * @param array<TableFieldEntity> $fields
     * @param array $options
     * @param array|null $rowData
     *
     * @return array
     */
    public function getInputValues(array $fields, array $options, array|null $rowData = null): array
    {
        // From html.inc.php (function edit_form($table, $fields, $rowData, $update))
        $entries = [];
        foreach ($fields as $name => $field) {
            [$value, $function] = $this->getInputValue($field, $rowData);
            $autofocus = $this->action !== 'save';
            if ($autofocus !== false) {
                $autofocus = match(true) {
                    $field->autoIncrement => null,
                    $function === 'now' => null,
                    $function === 'uuid' => null,
                    default => true,
                };
            }

            $entries[$name] = [$field, $rowData, $value, $function, $autofocus];
        }

        // From html.inc.php (function edit_form(string $table, array $fields, $row, ?bool $update, string $error = ''))
        return $this->getRowDataFormInputs($entries, $options);
    }

    /**
     * @param array $result
     * @param array<string, TableFieldEntity> $fields
     * @param array $options
     *
     * @return array
     */
    public function getUpdatedRow(array $result, array $fields, array $options): array
    {
        $textLength = $options['select']['length'];
        $formatted = [];
        foreach ($result as $fieldName => $value) {
            $field = $fields[$fieldName];
            $value = $this->driver->value($value, $field);
            $formatted[] = $this->page->getFieldValue($field, $textLength, $value);
        }
        return $formatted;
    }
}

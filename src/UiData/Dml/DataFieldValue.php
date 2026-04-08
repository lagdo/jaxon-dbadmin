<?php

namespace Lagdo\DbAdmin\Db\UiData\Dml;

use Lagdo\DbAdmin\Db\Driver\AbstractProxy;
use Lagdo\DbAdmin\Db\UiData\AppPage;
use Lagdo\DbAdmin\Support\Utils\Utils;
use Lagdo\DbAdmin\Support\DriverInterface;
use Lagdo\DbAdmin\Support\Dto\TableFieldDto;
use Lagdo\DbAdmin\Support\Dto\UserTypeDto;

use function bin2hex;
use function implode;
use function is_array;
use function is_bool;
use function json_encode;
use function preg_match;

/**
 * Writes data in the user forms for data row insert and update.
 */
class DataFieldValue extends AbstractProxy
{
    /**
     * @var bool
     */
    private bool $isUpdate = false;

    /**
     * @var array<UserTypeDto>|null
     */
    private array|null $userTypes = null;

    /**
     * @var string
     */
    private string $action;

    /**
     * @var string
     */
    private string $operation;

    /**
     * @param string $action
     * @param string $operation
     *
     * @return self
     */
    public function init(string $action, string $operation): self
    {
        $this->action = $action;
        $this->operation = $operation;
        $this->isUpdate = $operation === 'update';
        return $this;
    }

    /**
     * @param TableFieldDto $field
     *
     * @return UserTypeDto|null
     */
    public function userType(TableFieldDto $field): UserTypeDto|null
    {
        $this->userTypes ??= $this->driver()->userTypes(true);
        return $this->userTypes[$field->type] ?? null;
    }

    /**
     * @param array $names
     * @param array $functions
     * @param bool $addSql
     * @param TableFieldDto $field
     *
     * @return array
     */
    private function addEditFunctions(array $names, array $functions, TableFieldDto $field): array
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
     * @param TableFieldDto $field Single field from fields()
     *
     * @return array
     */
    private function editFunctions(TableFieldDto $field): array
    {
        if ($field->autoIncrement && !$this->isUpdate) {
            return [$this->utils()->lang('Auto Increment')];
        }

        $names = $field->nullable ? ['NULL', ''] : [''];
        $functions = $this->driver()->insertFunctions();
        $names = $this->addEditFunctions($names, $functions, $field);

        $functions = $this->driver()->editFunctions();
        if (/*!isset($this->utils()->input->values['call']) &&*/ $this->isUpdate) { // relative functions
            $names = $this->addEditFunctions($names, $functions, $field);
        }

        $structuredTypes = $this->driver()->structuredTypes();
        $userTypes = $structuredTypes[$this->utils()->lang('User types')] ?? [];
        if ($functions && !preg_match('~set|bool~', $field->type) &&
            !$this->utils()->isBlob($field, $userTypes)) {
            $names[] = 'SQL';
        }

        // $dbFunctions = [
        //     'insert' => $this->driver()->insertFunctions(),
        //     'edit' => $this->driver()->editFunctions(),
        // ];
        // foreach ($dbFunctions as $key => $functions) {
        //     if ($key === 'insert' || (!$isCall && $this->isUpdate)) { // relative functions
        //         foreach ($functions as $pattern => $value) {
        //             if (!$pattern || preg_match("~$pattern~", $field->type)) {
        //                 $names = [...$names, ...$value]; // Array merge
        //             }
        //         }
        //     }
        //     if ($key === 'edit' && !preg_match('~set|bool~', $field->type) && !$this->utils()->isBlob($field, $userTypes)) {
        //         $names[] = 'SQL';
        //     }
        // }

        return $names;
    }

    /**
     * @param TableFieldDto $field
     * @param array|null $rowData
     *
     * @return mixed
     */
    private function getInputValue(TableFieldDto $field, array|null $rowData): mixed
    {
        $update = $this->operation === 'update';
        // $default = $options["set"][$this->grammar()->bracketEscape($name)] ?? null;
        /*if ($default === null)*/ {
            $default = $field->default;
            if ($field->type == "bit" && preg_match("~^b'([01]*)'\$~", $default, $regs)) {
                $default = $regs[1];
            }
            if ($this->driver()->sql() && preg_match('~binary~', $field->type)) {
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

        $fieldValue = $rowData[$field->name] ?? null;
        return match(true) {
            $fieldValue !== '' && $this->driver()->sql() &&
                preg_match("~enum|set~", $field->type) > 0 &&
                is_array($fieldValue) => implode(",", $fieldValue),
            is_bool($fieldValue) => +$fieldValue,
            default => $fieldValue,
        };
    }

    /**
     * @param TableFieldDto $field
     * @param mixed $value
     *
     * @return array
     */
    private function getInputFunction(TableFieldDto $field, mixed $value): array
    {
        $formInput = []; // No user input available here.
        $update = $this->operation === 'update';
        $function = match(true) {
            $this->action === 'save' => $formInput['function'][$field->name] ?? '',
            $update && preg_match('~^CURRENT_TIMESTAMP~i', $field->onUpdate) > 0 => 'now',
            $value === false => null,
            $value !== null => '',
            default => 'NULL',
        };
        if ($this->action !== 'save' && !$update && $value === $field->default &&
            preg_match('~^[\w.]+\(~', $value ?? '') > 0) {
            $function = 'SQL';
        }
        if (preg_match('~time~', $field->type) > 0 &&
            preg_match('~^CURRENT_TIMESTAMP~i', $value ?? '') > 0) {
            $value = "";
            $function = "now";
        }
        if ($field->type === "uuid" && $value === "uuid()") {
            $value = "";
            $function = "uuid";
        }

        return [$value, $function];
    }

    /**
     * Get data for an input field
     *
     * @param TableFieldDto $field
     * @param array|null $rowData
     *
     * @return FieldEditDto
     */
    public function getFieldInputValues(TableFieldDto $field, array|null $rowData): FieldEditDto
    {
        $editField = new FieldEditDto($field);

        // From html.inc.php: function edit_form(string $table, array $fields, $row, ?bool $update, string $error = '')
        $value = $this->getInputValue($field, $rowData);
        // if (!$this->action !== 'save' && is_string($value)) {
        //     $value = adminer()->editVal($value, $field);
        // }
        [$editField->value, $editField->function] = $this->getInputFunction($field, $value);

        // From html.inc.php: input(array $field, $value, ?string $function, ?bool $autofocus = false)
        $editField->name = $this->utils()->html($this->grammar()->bracketEscape($field->name));
        $editField->fullType = $this->utils()->html($field->fullType);

        if (is_array($editField->value) && !$editField->function) {
             // 128 - JSON_PRETTY_PRINT, 64 - JSON_UNESCAPED_SLASHES, 256 - JSON_UNESCAPED_UNICODE available since PHP 5.4
            $editField->value = json_encode($editField->value,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $editField->function = 'json';
        }

        // Since mssql is not yet supported, $reset is always false.
        // $reset = $this->driver()->mssql() && $field->autoIncrement;
        // if ($reset && $this->action !== 'save') {
        //     $editField->function = null;
        // }

        // $editField->functions = [];
        // if ($reset) {
        //     $editField->functions['orig'] = $this->utils()->lang('original');
        // }
        // $editField->functions = [...$editField->functions, ...$this->editFunctions($field)];
        $editField->functions = $this->editFunctions($field);

        $userType = $this->userType($field);
        $editField->enums = $userType?->enums ?? [];
        if ($editField->enums) {
            $editField->type = 'enum';
            $editField->field->length = $editField->enumsLength();
        }

        // Todo: process the output of tis function, which is available on MySQL only.
        // echo driver()->unconvertFunction($field) . " ";

        return $editField;
    }
}

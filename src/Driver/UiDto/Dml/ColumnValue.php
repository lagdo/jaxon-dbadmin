<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dml;

use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\UserTypeDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;

use function bin2hex;
use function implode;
use function is_array;
use function is_bool;
use function json_encode;
use function preg_match;

/**
 * Writes data in the user forms for data row insert and update.
 */
class ColumnValue extends AbstractDriverProxy
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
    public function action(string $action, string $operation): self
    {
        $this->action = $action;
        $this->operation = $operation;
        $this->isUpdate = $operation === 'update';
        return $this;
    }

    /**
     * @param ColumnDto $column
     *
     * @return UserTypeDto|null
     */
    public function userType(ColumnDto $column): UserTypeDto|null
    {
        $this->userTypes ??= $this->engine()->userTypes(true);
        return $this->userTypes[$column->type] ?? null;
    }

    /**
     * @param array $names
     * @param array $functions
     * @param ColumnDto $column
     *
     * @return array
     */
    private function addEditFunctions(array $names, array $functions, ColumnDto $column): array
    {
        foreach ($functions as $pattern => $_functions) {
            if (!$pattern || preg_match("~$pattern~", $column->type)) {
                $names = [...$names, ...$_functions]; // Array merge
            }
        }
        return $names;
    }

    /**
     * Functions displayed in edit form
     * function editFunctions() in adminer.inc.php
     *
     * @param ColumnDto $column Single column from columns()
     *
     * @return array
     */
    private function editFunctions(ColumnDto $column): array
    {
        if ($column->autoIncrement && !$this->isUpdate) {
            return [$this->utils()->lang('Auto Increment')];
        }

        $names = $column->nullable ? ['NULL', ''] : [''];
        $functions = $this->engine()->insertFunctions();
        $names = $this->addEditFunctions($names, $functions, $column);

        $functions = $this->engine()->editFunctions();
        if (/*!isset($this->utils()->input->values['call']) &&*/ $this->isUpdate) {
            // relative functions
            $names = $this->addEditFunctions($names, $functions, $column);
        }

        $structuredTypes = $this->engine()->structuredTypes();
        $userTypes = $structuredTypes[$this->utils()->lang('User types')] ?? [];
        if ($functions && !preg_match('~set|bool~', $column->type) &&
            !$this->utils()->isBlob($column, $userTypes)) {
            $names[] = 'SQL';
        }

        // $dbFunctions = [
        //     'insert' => $this->engine()->insertFunctions(),
        //     'edit' => $this->engine()->editFunctions(),
        // ];
        // foreach ($dbFunctions as $key => $functions) {
        //     if ($key === 'insert' || (!$isCall && $this->isUpdate)) { // relative functions
        //         foreach ($functions as $pattern => $value) {
        //             if (!$pattern || preg_match("~$pattern~", $column->type)) {
        //                 $names = [...$names, ...$value]; // Array merge
        //             }
        //         }
        //     }
        //     if ($key === 'edit' && !preg_match('~set|bool~', $column->type) && !$this->utils()->isBlob($column, $userTypes)) {
        //         $names[] = 'SQL';
        //     }
        // }

        return $names;
    }

    /**
     * @param ColumnDto $column
     * @param array|null $rowData
     *
     * @return mixed
     */
    private function getInputValue(ColumnDto $column, array|null $rowData): mixed
    {
        $update = $this->operation === 'update';
        // $default = $options["set"][$this->statement()->bracketEscape($name)] ?? null;
        /*if ($default === null)*/ {
            $default = $column->default;
            if ($column->type == "bit" && preg_match("~^b'([01]*)'\$~", $default, $regs)) {
                $default = $regs[1];
            }
            if ($this->engine()->sql() && preg_match('~binary~', $column->type)) {
                $default = bin2hex($default); // same as UNHEX
            }
        }

        if ($rowData === null) {
            return match(true) {
                !$update && $column->autoIncrement => '',
                $this->action === 'select' => false,
                default => $default,
            };
        }

        $columnValue = $rowData[$column->name] ?? null;
        return match(true) {
            $columnValue !== '' && $this->engine()->sql() &&
                preg_match("~enum|set~", $column->type) > 0 &&
                is_array($columnValue) => implode(",", $columnValue),
            is_bool($columnValue) => +$columnValue,
            default => $columnValue,
        };
    }

    /**
     * @param ColumnDto $column
     * @param mixed $value
     *
     * @return array
     */
    private function getInputFunction(ColumnDto $column, mixed $value): array
    {
        $formInput = []; // No user input available here.
        $update = $this->operation === 'update';
        $function = match(true) {
            $this->action === 'save' => $formInput['function'][$column->name] ?? '',
            $update && preg_match('~^CURRENT_TIMESTAMP~i', $column->onUpdate) > 0 => 'now',
            $value === false => null,
            $value !== null => '',
            default => 'NULL',
        };
        if ($this->action !== 'save' && !$update && $value === $column->default &&
            preg_match('~^[\w.]+\(~', $value ?? '') > 0) {
            $function = 'SQL';
        }
        if (preg_match('~time~', $column->type) > 0 &&
            preg_match('~^CURRENT_TIMESTAMP~i', $value ?? '') > 0) {
            $value = '';
            $function = 'now';
        }
        if ($column->type === 'uuid' && $value === 'uuid()') {
            $value = '';
            $function = 'uuid';
        }

        return [$value, $function];
    }

    /**
     * Get data for an input field
     *
     * @param ColumnDto $column
     * @param array|null $rowData
     *
     * @return ColumnDmDto
     */
    public function getColumnInputValues(ColumnDto $column, array|null $rowData): ColumnDmDto
    {
        $input = new ColumnDmDto($column);

        // From html.inc.php: function edit_form(string $table, array $columns, $row, ?bool $update, string $error = '')
        $value = $this->getInputValue($column, $rowData);
        // if (!$this->action !== 'save' && is_string($value)) {
        //     $value = adminer()->editVal($value, $column);
        // }
        [$input->value, $input->function] = $this->getInputFunction($column, $value);

        // From html.inc.php: input(array $column, $value, ?string $function, ?bool $autofocus = false)
        $input->name = $this->utils()->html($this->statement()->bracketEscape($column->name));
        $input->fullType = $this->utils()->html($column->fullType);

        if (is_array($input->value) && !$input->function) {
            // 128 - JSON_PRETTY_PRINT, 64 - JSON_UNESCAPED_SLASHES, 256 - JSON_UNESCAPED_UNICODE available since PHP 5.4
            $input->value = json_encode($input->value,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $input->function = 'json';
        }

        // Since mssql is not yet supported, $reset is always false.
        // $reset = $this->engine()->mssql() && $column->autoIncrement;
        // if ($reset && $this->action !== 'save') {
        //     $input->function = null;
        // }

        // $input->functions = [];
        // if ($reset) {
        //     $input->functions['orig'] = $this->utils()->lang('original');
        // }
        // $input->functions = [...$input->functions, ...$this->editFunctions($column)];
        $input->functions = $this->editFunctions($column);

        $userType = $this->userType($column);
        $input->enums = $userType?->enums ?? [];
        if ($input->enums) {
            $input->type = 'enum';
            $input->column->length = $input->enumList();
        }

        // Todo: process the output of tis function, which is available on MySQL only.
        // echo driver()->unconvertFunction($column) . ' ';

        return $input;
    }
}

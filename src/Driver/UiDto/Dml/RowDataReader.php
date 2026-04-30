<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dml;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\UserTypeDto;

use function count;
use function implode;
use function is_array;
use function is_string;
use function json_decode;
use function preg_match;
use function substr;

/**
 * Reads data from the user inputs for data row insert and update.
 */
class RowDataReader extends AbstractDriverProxy
{
    /**
     * @var array<UserTypeDto>|null
     */
    private array|null $userTypes = null;

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
     * Get the user input values for data save on insert and update
     * Function process_input() in html.inc.php.
     *
     * @param ColumnDto $column
     * @param array $values
     *
     * @return mixed
     */
    private function getInputValue(ColumnDto $column, array $values): mixed
    {
        if ($column->isDisabled()) {
            return false;
        }

        $columnId = $this->statement()->bracketEscape($column->name);
        $userType = $this->userType($column);
        $enumValues = $userType?->enums ?? [];
        if ($column->type === 'enum' || count($enumValues) > 0) {
            // An enum column with no value selected will have no entry in the values.
            $value = $values['input_values'][$columnId][0] ?? '';
            if ($value === 'orig') {
                return false;
            }
            if ($value === 'null') {
                return 'NULL';
            }

            $value = substr($value, 4); // 4 - strlen('val-')
            // There's no function on enum columns.
            return $this->statement()->getUnconvertedFieldValue($column, $value);
        }

        $value = $values['input_values'][$columnId] ?? '';

        if ($column->autoIncrement && $value === '') {
            return null;
        }

        // The function is not provided for auto-incremented columns or enums.
        $function = $values['input_functions'][$columnId] ?? '';
        if ($function === 'orig') {
            return preg_match('~^CURRENT_TIMESTAMP~i', $column->onUpdate) ?
                $this->statement()->escapeId($column->name) : false;
        }

        if ($function === 'NULL') {
            return 'NULL';
        }

        if ($column->type === 'set') {
            $value = implode(',', (array)$value);
        }

        if ($function === 'json') {
            $function = '';
            $value = json_decode($value, true);
            //! report errors
            return is_array($value) ? $value : false;
        }

        if ($this->utils()->isBlob($column) && $this->utils()->iniBool('file_uploads')) {
            $file = $this->pageUi()->getFileContents("columns-$columnId");
            //! report errors
            return is_string($file) ? $this->engine()->quoteBinary($file) : false;
        }

        return $this->statement()->getUnconvertedFieldValue($column, $value, $function);
    }

    /**
     * @param array<ColumnDto> $columns The table columns
     * @param array $inputs The user form inputs
     *
     * @return array
     */
    public function getInputValues(array $columns, array $inputs): array
    {
        // From edit.inc.php
        $values = [];
        foreach ($columns as $name => $column) {
            $value = $this->getInputValue($column, $inputs);
            if ($value !== false && $value !== null) {
                $values[$this->statement()->escapeId($name)] = $value;
            }
        }

        return $values;
    }
}

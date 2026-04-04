<?php

namespace Lagdo\DbAdmin\Db\UiData\Dml;

use Lagdo\DbAdmin\Db\Driver\AbstractProxy;
use Lagdo\DbAdmin\Db\UiData\AppPage;
use Lagdo\DbAdmin\Support\DriverInterface;
use Lagdo\DbAdmin\Support\Dto\TableFieldDto;
use Lagdo\DbAdmin\Support\Dto\UserTypeDto;
use Lagdo\DbAdmin\Support\Utils\Utils;

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
class DataRowReader extends AbstractProxy
{
    /**
     * @var array<UserTypeDto>|null
     */
    private array|null $userTypes = null;

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
     * Get the user input values for data save on insert and update
     * Function process_input() in html.inc.php.
     *
     * @param TableFieldDto $field
     * @param array $values
     *
     * @return mixed
     */
    private function getInputValue(TableFieldDto $field, array $values): mixed
    {
        if ($field->isDisabled()) {
            return false;
        }

        $fieldId = $this->grammar()->bracketEscape($field->name);
        $userType = $this->userType($field);
        $enumValues = $userType?->enums ?? [];
        if ($field->type === "enum" || count($enumValues) > 0) {
            // An enum field with no value selected will have no entry in the values.
            $value = $values['field_values'][$fieldId][0] ?? '';
            if ($value === "orig") {
                return false;
            }
            if ($value === "null") {
                return "NULL";
            }

            $value = substr($value, 4); // 4 - strlen("val-")
            // There's no function on enum fields.
            return $this->page()->getUnconvertedFieldValue($field, $value);
        }

        $value = $values['field_values'][$fieldId] ?? '';

        if ($field->autoIncrement && $value === '') {
            return null;
        }

        // The function is not provided for auto-incremented fields or enums.
        $function = $values['field_functions'][$fieldId] ?? '';
        if ($function === 'orig') {
            return preg_match('~^CURRENT_TIMESTAMP~i', $field->onUpdate) ?
                $this->grammar()->escapeId($field->name) : false;
        }

        if ($function === 'NULL') {
            return 'NULL';
        }

        if ($field->type === 'set') {
            $value = implode(',', (array)$value);
        }

        if ($function === 'json') {
            $function = '';
            $value = json_decode($value, true);
            //! report errors
            return is_array($value) ? $value : false;
        }

        if ($this->utils()->isBlob($field) && $this->utils()->iniBool('file_uploads')) {
            $file = $this->page()->getFileContents("fields-$fieldId");
            //! report errors
            return is_string($file) ? $this->driver()->quoteBinary($file) : false;
        }

        return $this->page()->getUnconvertedFieldValue($field, $value, $function);
    }

    /**
     * @param array<TableFieldDto> $fields The table fields
     * @param array $inputs The user form inputs
     *
     * @return array
     */
    public function getInputValues(array $fields, array $inputs): array
    {
        // From edit.inc.php
        $values = [];
        foreach ($fields as $name => $field) {
            $value = $this->getInputValue($field, $inputs);
            if ($value !== false && $value !== null) {
                $values[$this->grammar()->escapeId($name)] = $value;
            }
        }

        return $values;
    }
}

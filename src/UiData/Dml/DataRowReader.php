<?php

namespace Lagdo\DbAdmin\Db\UiData\Dml;

use Lagdo\DbAdmin\Db\UiData\AppPage;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Driver\Dto\TableFieldDto;
use Lagdo\DbAdmin\Driver\Dto\UserTypeDto;
use Lagdo\DbAdmin\Driver\Utils\Utils;

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
class DataRowReader
{
    /**
     * @var array<UserTypeDto>
     */
    private array $userTypes;

    /**
     * The constructor
     *
     * @param AppPage $page
     * @param DriverInterface $driver
     * @param Utils $utils
     */
    public function __construct(private AppPage $page,
        private DriverInterface $driver, private Utils $utils)
    {
        $this->userTypes = $this->driver->userTypes(true);
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

        $fieldId = $this->driver->bracketEscape($field->name);
        $userType = $this->userTypes[$field->type] ?? null;
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
            return $this->page->getUnconvertedFieldValue($field, $value);
        }

        $value = $values['field_values'][$fieldId] ?? '';

        if ($field->autoIncrement && $value === '') {
            return null;
        }

        // The function is not provided for auto-incremented fields or enums.
        $function = $values['field_functions'][$fieldId] ?? '';
        if ($function === 'orig') {
            return preg_match('~^CURRENT_TIMESTAMP~i', $field->onUpdate) ?
                $this->driver->escapeId($field->name) : false;
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

        if ($this->utils->isBlob($field) && $this->utils->iniBool('file_uploads')) {
            $file = $this->page->getFileContents("fields-$fieldId");
            //! report errors
            return is_string($file) ? $this->driver->quoteBinary($file) : false;
        }

        return $this->page->getUnconvertedFieldValue($field, $value, $function);
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
                $values[$this->driver->escapeId($name)] = $value;
            }
        }

        return $values;
    }
}

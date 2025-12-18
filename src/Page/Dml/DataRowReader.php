<?php

namespace Lagdo\DbAdmin\Db\Page\Dml;

use Lagdo\DbAdmin\Db\Page\Traits\InputFieldTrait;
use Lagdo\DbAdmin\Driver\Utils\Utils;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function count;
use function implode;
use function is_array;
use function is_string;
use function json_decode;
use function preg_match;
use function substr;

/**
 * Reads data from the user inputs
 */
class DataRowReader
{
    use InputFieldTrait;

    /**
     * The constructor
     *
     * @param DriverInterface $driver
     * @param Utils $utils
     */
    public function __construct(private DriverInterface $driver, private Utils $utils)
    {}

    /**
     * Get the user input values for data save on insert and update
     * Function process_input() in html.inc.php.
     *
     * @param TableFieldEntity $field
     * @param array $values
     * @param array $enumValues
     *
     * @return mixed
     */
    private function getInputValue(TableFieldEntity $field, array $values, array $enumValues): mixed
    {
        if ($field->isDisabled()) {
            return false;
        }

        $fieldId = $this->driver->bracketEscape($field->name);
        $value = $values['fields'][$fieldId];
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

        // The function is not provided for auto-incremented fields.
        $function = $values['function'][$fieldId];
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
            $file = $this->getFileContents("fields-$fieldId");
            //! report errors
            return !is_string($file) ? false : $this->driver->quoteBinary($file);
        }
        return $this->getUnconvertedFieldValue($field, $value, $function);
    }

    /**
     * @param array<TableFieldEntity> $fields
     * @param array $inputs
     *
     * @return array
     */
    public function getInputValues(array $fields, array $inputs): array
    {
        $userTypes = $this->driver->userTypes(true);
        // From edit.inc.php
        $values = [];
        foreach ($fields as $name => $field) {
            $userType = $userTypes[$field->type] ?? null;
            $enumValues = !$userType ? [] : $userType->enums;
            $value = $this->getInputValue($field, $inputs, $enumValues);
            if ($value !== false && $value !== null) {
                $values[$this->driver->escapeId($name)] = $value;
            }
        }
        return $values;
    }
}

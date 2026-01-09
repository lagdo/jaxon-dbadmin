<?php

namespace Lagdo\DbAdmin\Db\UiData\Dml;

use Lagdo\DbAdmin\Db\UiData\AppPage;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Driver\Dto\TableFieldDto;
use Lagdo\DbAdmin\Driver\Utils\Utils;

/**
 * Reads data from the database for the row insert and update user forms.
 */
class DataRowWriter
{
    /**
     * @var bool|null
     */
    private bool|null $autofocus;

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
        private Utils $utils, private string $action, private string $operation,
        private DataFieldValue $fieldValue, private DataFieldInput $fieldInput)
    {}

    /**
     * @param array $result
     * @param array<string, TableFieldDto> $fields
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

    /**
     * @param array<TableFieldDto> $fields
     * @param array|null $rowData
     *
     * @return array<FieldEditDto>
     */
    public function getInputValues(array $fields, array|null $rowData = null): array
    {
        // From html.inc.php (function edit_form($table, $fields, $rowData, $update))
        $this->autofocus = $this->action !== 'save';

        $entries = [];
        foreach ($fields as $name => $field) {
            $editField = $this->fieldValue->getFieldInputValues($field, $rowData);

            if ($this->autofocus !== false) {
                $this->autofocus = match(true) {
                    $field->autoIncrement => null,
                    $editField->function === 'now' => null,
                    $editField->function === 'uuid' => null,
                    default => true,
                };
            }

            // Format the data fields for the user input form.
            $this->fieldInput->setFieldInputValues($editField, $this->autofocus);

            $entries[$name] = $editField;

            if ($this->autofocus) {
                $this->autofocus = false;
            }
        }

        return $entries;
    }
}

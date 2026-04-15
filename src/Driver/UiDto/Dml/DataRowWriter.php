<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dml;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableFieldDto;

/**
 * Reads data from the database for the row insert and update user forms.
 */
class DataRowWriter extends AbstractDriverProxy
{
    /**
     * @var string
     */
    private string $action;

    /**
     * @var string
     */
    private string $operation;

    /**
     * @var DataFieldValue
     */
    private DataFieldValue $fieldValue;

    /**
     * @var DataFieldInput
     */
    private DataFieldInput $fieldInput;

    /**
     * @var bool|null
     */
    private bool|null $autofocus;

    /**
     * @param string $action
     * @param string $operation
     * @param DataFieldValue $fieldValue
     * @param DataFieldInput $fieldInput
     *
     * @return self
     */
    public function init(string $action, string $operation,
        DataFieldValue $fieldValue, DataFieldInput $fieldInput): self
    {
        $this->action = $action;
        $this->operation = $operation;
        $this->fieldValue = $fieldValue;
        $this->fieldInput = $fieldInput;
        return $this;
    }

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
            $value = $this->engine()->value($value, $field);
            $formatted[] = $this->pageUi()->getFieldValue($field, $textLength, $value);
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

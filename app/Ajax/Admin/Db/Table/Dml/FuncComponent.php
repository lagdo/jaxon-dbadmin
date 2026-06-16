<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dml;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\FuncComponent as BaseComponent;

use function in_array;
use function is_array;

abstract class FuncComponent extends BaseComponent
{
    /**
     * Build the form data with the edited values
     *
     * @param array $queryFields
     * @param array $formValues
     *
     * @return array
     */
    protected function getEditedFormValues(array $queryFields, array $formValues): array
    {
        // Update the functions
        foreach ($formValues['input_functions'] ?? [] as $column => $function) {
            // Make sure the column is present.
            if (!isset($queryFields[$column])) {
                continue;
            }

            $queryField = $queryFields[$column];
            if (isset($queryField->functionInput['select'])) {
                $queryField->functionInput['select']['value'] = $function;
            }
        }

        // Update the values
        foreach ($formValues['input_values'] ?? [] as $column => $value)
        {
            // Make sure the column is present.
            if (!isset($queryFields[$column])) {
                continue;
            }

            $queryField = $queryFields[$column];
            // The column has a simple value.
            if (isset($queryField->valueInput['value'])) {
                $queryField->valueInput['value'] = $value;
                continue;
            }
            // The column is a checkbox for a boolean.
            if ($queryField->valueInput['field'] === 'bool') {
                $queryField->valueInput['checked'] = $value === '1';
                continue;
            }
            // The column is a file upload.
            if ($queryField->valueInput['field'] === 'file') {
                continue;
            }

            // The column has an array value (set or enum).
            if (isset($queryField->valueInput['items']) && is_array($value)) {
                foreach ($queryField->valueInput['items'] as &$item) {
                    $item['checked'] = in_array($item['value'], $value);
                }
            }
        }

        return $queryFields;
    }
}

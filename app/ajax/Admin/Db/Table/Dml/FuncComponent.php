<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dml;

use Lagdo\DbAdmin\Ajax\Admin\Db\Database\Query;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\FuncComponent as BaseComponent;
use Lagdo\DbAdmin\Ui\Data\EditUiBuilder;

use function in_array;
use function is_array;

abstract class FuncComponent extends BaseComponent
{
    /**
     * The constructor
     *
     * @param EditUiBuilder  $editUi     The HTML UI builder
     */
    public function __construct(protected EditUiBuilder $editUi)
    {}

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
        foreach ($formValues['field_functions'] ?? [] as $field => $function) {
            // Make sure the field is present.
            if (!isset($queryFields[$field])) {
                continue;
            }

            $queryField = $queryFields[$field];

            if (isset($queryField->functionInput['select'])) {
                $queryField->functionInput['select']['value'] = $function;
            }
        }

        // Update the values
        foreach ($formValues['field_values'] ?? [] as $field => $value)
        {
            // Make sure the field is present.
            if (!isset($queryFields[$field])) {
                continue;
            }

            $queryField = $queryFields[$field];

            // The field has a simple value.
            if (isset($queryField->valueInput['value'])) {
                $queryField->valueInput['value'] = $value;
                continue;
            }

            // The field is a checkbox for a boolean.
            if ($queryField->valueInput['field'] === 'bool') {
                $queryField->valueInput['checked'] = $value === '1';
                continue;
            }

            // The field is a file upload.
            if ($queryField->valueInput['field'] === 'file') {
                continue;
            }

            // The field has an array value (set or enum).
            if (isset($queryField->valueInput['items']) && is_array($value)) {
                foreach ($queryField->valueInput['items'] as &$item) {
                    $item['checked'] = in_array($item['value'], $value);
                }
            }
        }

        return $queryFields;
    }

    /**
     * @param string $title
     * @param string $query
     * @param array $buttons
     *
     * @return void
     */
    protected function showQueryCodeDialog(string $title, string $query, array $buttons = []): void
    {
        // Show the query in a modal dialog.
        $title = $this->trans()->lang($title);
        $content = $this->editUi->sqlCodeElement($query);
        // Bootbox options
        $options = ['size' => 'large'];
        $buttons = [[
            'title' => $this->trans()->lang('Close'),
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ], [
            'title' => $this->trans()->lang('Edit'),
            'class' => 'btn btn-primary',
            'click' => $this->rq(Query::class)->database($query),
        ], ...$buttons];

        $this->modal()->show($title, $content, $buttons, $options);

        $this->setupSqlEditor($this->editUi->queryDivId());
    }
}

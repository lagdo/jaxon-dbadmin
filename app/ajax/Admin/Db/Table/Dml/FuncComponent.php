<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dml;

use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\Ajax\Admin\Db\Database\Query;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\ComponentTrait;
use Lagdo\DbAdmin\Ajax\FuncComponent as BaseFuncComponent;
use Lagdo\DbAdmin\Db\Config\ServerConfig;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Data\EditUiBuilder;

use function in_array;
use function is_array;

#[Before('checkDatabaseAccess')]
abstract class FuncComponent extends BaseFuncComponent
{
    use ComponentTrait;

    /**
     * The constructor
     *
     * @param ServerConfig   $config     The package config reader
     * @param DbFacade       $db         The facade to database functions
     * @param EditUiBuilder  $editUi     The HTML UI builder
     * @param Translator     $trans
     */
    public function __construct(protected ServerConfig $config, protected DbFacade $db,
        protected EditUiBuilder $editUi, protected Translator $trans)
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
        $queryDivId = 'dbadmin-table-show-sql-query';
        $title = $this->trans()->lang($title);
        $content = $this->editUi->sqlCodeElement($queryDivId, $query);
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

        $this->setupSqlEditor($queryDivId);
    }
}

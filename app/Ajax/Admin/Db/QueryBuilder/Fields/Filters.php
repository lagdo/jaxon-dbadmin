<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder\Fields;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder\Fields;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder\FuncComponent;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\QueryText;

use function array_filter;
use function Jaxon\form;

/**
 * This class provides select query features on tables.
 */
class Filters extends FuncComponent
{
    /**
     * Change the query filters
     *
     * @return void
     */
    public function edit(): void
    {
        $title = 'Edit filters';
        $content = $this->optionsUi->editFilters();
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ], [
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->save(form($this->optionsUi->filterFormId())),
        ]];
        $this->modal()->show($title, $content, $buttons);

        // Display the current values in the form.
        $this->cl(Form\Filters::class)->show();
    }

    /**
     * Change the query filters
     *
     * @param array  $values  The form values
     *
     * @return void
     */
    public function save(array $values): void
    {
        // Save the new values in the databag.
        $delete = fn(array $value) => empty($value['delete']);
        $filters = array_filter($values['filters'] ?? [], $delete);
        $this->saveParamValue('filters', $filters);

        // Hide the dialog
        $this->modal()->hide();

        // Display the new query
        $this->cl(QueryText::class)->refresh();

        $this->cl(Fields::class)->render();
    }
}

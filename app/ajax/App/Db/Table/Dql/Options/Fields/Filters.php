<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Options\Fields;

use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\FuncComponent;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\QueryText;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Options\Fields;

use function Jaxon\pm;

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
        $formId = 'dbadmin-table-select-filters-form';
        $title = 'Edit filters';
        $content = $this->selectUi->editFilters($formId);
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->save(pm()->form($formId)),
        ]];
        $this->modal()->show($title, $content, $buttons);

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
        $this->bag('dbadmin.select')->set('filters', $values);

        // Hide the dialog
        $this->modal()->hide();

        // Display the new query
        $this->cl(QueryText::class)->refresh();

        $this->cl(Fields::class)->render();
    }
}

<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Options\Fields;

use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\FuncComponent;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\QueryText;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Options\Fields;

use function Jaxon\pm;

/**
 * This class provides select query features on tables.
 */
class Sorting extends FuncComponent
{
    /**
     * Change the query sorting
     *
     * @return void
     */
    public function edit(): void
    {
        $formId = 'dbadmin-table-select-sorting-form';
        $title = 'Edit order';
        $content = $this->selectUi->editSorting($formId);
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

        $this->cl(Form\Sorting::class)->show();
    }

    /**
     * Change the query sorting
     *
     * @param array  $values  The form values
     *
     * @return void
     */
    public function save(array $values): void
    {
        // Save the new values in the databag.
        $this->bag('dbadmin.select')->set('sorting', $values);

        // Hide the dialog
        $this->modal()->hide();

        // Display the new query
        $this->cl(QueryText::class)->refresh();

        $this->cl(Fields::class)->render();
    }
}

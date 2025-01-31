<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dql;

use Lagdo\DbAdmin\Ajax\App\Db\Table\CallableClass;

use function Jaxon\pm;

/**
 * This class provides select query features on tables.
 */
class Sorting extends CallableClass
{
    /**
     * The sorting form div id
     *
     * @var string
     */
    private $formId = 'adminer-table-select-sorting-form';

    /**
     * Change the query sorting
     *
     * @return void
     */
    public function edit()
    {
        $title = 'Edit order';
        $content = $this->ui()->editQuerySorting($this->formId);
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->save(pm()->form($this->formId)),
        ]];
        $this->modal()->show($title, $content, $buttons);

        $this->cl(Input\Sorting::class)->show();
    }

    /**
     * Change the query sorting
     *
     * @param array  $values  The form values
     *
     * @return void
     */
    public function save(array $values)
    {
        // Save the new values in the databag.
        $this->bag('dbadmin.select')->set('sorting', $values);

        // Hide the dialog
        $this->modal()->hide();

        // Display the new query
        $this->cl(QueryText::class)->refresh();
    }
}

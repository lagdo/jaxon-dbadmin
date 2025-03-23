<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dql;

use Lagdo\DbAdmin\Ajax\App\Db\Table\FuncComponent;

use function Jaxon\pm;

/**
 * This class provides select query features on tables.
 */
class Columns extends FuncComponent
{
    /**
     * The columns form div id
     *
     * @var string
     */
    private $formId = 'adminer-table-select-columns-form';

    /**
     * Change the query columns
     *
     * @return void
     */
    public function edit()
    {
        $title = 'Edit columns';
        $content = $this->ui()->editQueryColumns($this->formId);
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

        $this->cl(Input\Columns::class)->show();
    }

    /**
     * Change the query columns
     *
     * @param array  $values  The form values
     *
     * @return void
     */
    public function save(array $values)
    {
        // Save the new values in the databag.
        $this->bag('dbadmin.select')->set('columns', $values);

        // Hide the dialog
        $this->modal()->hide();

        // Display the new query
        $this->cl(QueryText::class)->refresh();
    }
}

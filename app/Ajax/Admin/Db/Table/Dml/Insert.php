<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dml;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\ResultSet;

use function Jaxon\form;

/**
 * This class provides insert and update query features on tables.
 */
#[Databag('dbadmin.select')]
class Insert extends FuncComponent
{
    /**
     * @param bool $fromSelect
     * @param array $columns
     *
     * @return void
     */
    private function showQueryDataDialog(bool $fromSelect, array $columns): void
    {
        $title = 'New item in table ' . $this->getCurrentTable();
        $content = $this->editUi->rowDataForm($columns);
        $values = form($this->editUi->queryFormId());
        // Bootbox options
        $options = ['size' => 'large'];
        $buttons = [[
            'title' => $this->trans()->lang('Cancel'),
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ], [
            'title' => $this->trans()->lang('Query'),
            'class' => 'btn btn-primary',
            'click' => $this->rq()->showQueryCode($fromSelect, $values),
        ], [
            'title' => $this->trans()->lang('Insert'),
            'class' => 'btn btn-primary',
            'click' => $this->rq()->save($fromSelect, $values)
                ->confirm($this->trans()->lang('Save this item?')),
        ]];

        $this->modal()->show($title, $content, $buttons, $options);
    }

    /**
     * @param bool $fromSelect
     *
     * @return void
     */
    public function show(bool $fromSelect): void
    {
        $insertData = $this->db()->getInsertData($this->getCurrentTable());
        // Show the error
        if(isset($insertData['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($insertData['error']);
            return;
        }

        $this->showQueryDataDialog($fromSelect, $insertData['columns']);
    }

    /**
     * Execute the insert query
     *
     * @param bool $fromSelect
     * @param array $formValues
     *
     * @return void
     */
    public function save(bool $fromSelect, array $formValues): void
    {
        // No specific options for inserts.
        $result = $this->db()->insertItem($this->getCurrentTable(), [], $formValues);
        // Show the error
        if(isset($result['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($result['error']);
            return;
        }

        // Refresh the result set.
        if ($fromSelect) {
            $this->cl(ResultSet::class)->page();
        }

        $this->modal()->hide();
        $this->alert()
            ->title($this->trans()->lang('Success'))
            ->success($result['message']);
    }

    /**
     * Back to the insert form
     *
     * @param bool $fromSelect
     * @param array $formValues
     *
     * @return void
     */
    public function showQueryForm(bool $fromSelect, array $formValues): void
    {
        // We need the table columns to be able to go back to the update form.
        $insertData = $this->db()->getInsertData($this->getCurrentTable());
        // Show the error
        if(isset($insertData['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($insertData['error']);
            return;
        }

        // Show the query in a modal dialog.
        $this->modal()->hide();

        $columns = $this->getEditedFormValues($insertData['columns'], $formValues);
        $this->showQueryDataDialog($fromSelect, $columns);
    }

    /**
     * Show the insert query
     *
     * @param bool $fromSelect
     * @param array $formValues
     *
     * @return void
     */
    public function showQueryCode(bool $fromSelect, array $formValues): void
    {
        // No specific options for inserts.
        $result = $this->db()->getRowInsertQuery($this->getCurrentTable(), [], $formValues);
        // Show the error
        if(isset($result['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($result['error']);
            return;
        }

        // Show the query in a modal dialog.
        $this->modal()->hide();

        $buttons = [[
            'title' => $this->trans()->lang('Back'),
            'class' => 'btn btn-primary',
            'click' => $this->rq()->showQueryForm($fromSelect, $formValues),
        ]];
        $this->showQueryCodeDialog('SQL query for insert', $result['query'], $buttons);
    }
}

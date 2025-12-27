<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dml;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\ResultSet;

use function Jaxon\je;

/**
 * This class provides insert and update query features on tables.
 */
#[Databag('dbadmin.select')]
class Insert extends FuncComponent
{
    /**
     * The query form div id
     *
     * @var string
     */
    private $queryFormId = 'dbadmin-table-query-form';

    /**
     * @param bool $fromSelect
     * @param array $fields
     *
     * @return void
     */
    private function showQueryDataForm(bool $fromSelect, array $fields): void
    {
        $title = 'New item in table ' . $this->getTableName();
        $content = $this->editUi->rowDataForm($this->queryFormId, $fields);
        $values = je($this->queryFormId)->rd()->form();
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
            'title' => $this->trans()->lang('Save'),
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
        $insertData = $this->db()->getInsertData($this->getTableName());
        // Show the error
        if(isset($insertData['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($insertData['error']);
            return;
        }

        $this->showQueryDataForm($fromSelect, $insertData['fields']);
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
        $result = $this->db()->insertItem($this->getTableName(), [], $formValues);
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
        // We need the table fields to be able to go back to the update form.
        $insertData = $this->db()->getInsertData($this->getTableName());
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

        $fields = $this->getEditedFormValues($insertData['fields'], $formValues);
        $this->showQueryDataForm($fromSelect, $fields);
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
        $result = $this->db()->getInsertQuery($this->getTableName(), [], $formValues);
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
        $this->showQueryCodeForm('SQL query for insert', $result['query'], $buttons);
    }
}

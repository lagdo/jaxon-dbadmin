<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dml;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\ResultRow;

use function count;
use function is_array;
use function Jaxon\form;

/**
 * This class provides insert and update query features on tables.
 */
#[Databag('dbadmin.select')]
class Update extends FuncComponent
{
    /**
     * The query form div id
     *
     * @var string
     */
    private $queryFormId = 'dbadmin-table-query-form';

    /**
     * @param int $editId
     * @param array $rowIds
     * @param array $fields
     *
     * @return void
     */
    private function showQueryDataDialog(int $editId, array $rowIds, array $fields): void
    {
        $title = 'Edit row in table ' . $this->getTableName();
        $content = $this->editUi->rowDataForm($this->queryFormId, $fields);
        $values = form($this->queryFormId);
        // Bootbox options
        $options = ['size' => 'large'];
        $buttons = [[
            'title' => $this->trans()->lang('Cancel'),
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ], [
            'title' => $this->trans()->lang('Query'),
            'class' => 'btn btn-primary',
            'click' => $this->rq()->showQueryCode($editId, $rowIds, $values),
        ], [
            'title' => $this->trans()->lang('Update'),
            'class' => 'btn btn-primary',
            'click' => $this->rq()->save($editId, $rowIds, $values)
                ->confirm($this->trans()->lang('Save this item?')),
        ]];

        $this->modal()->show($title, $content, $buttons, $options);
    }

    /**
     * @param int $editId
     * @param array $rowIds
     *
     * @return void
     */
    public function edit(int $editId, array $rowIds): void
    {
        if(!is_array($rowIds['where'] ?? 0) ||
            count($rowIds['where']) === 0 || $editId <= 0)
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Invalid query data');
            return;
        }

        $updateData = $this->db()->getUpdateData($this->getTableName(),  $rowIds);
        // Show the error
        if(isset($updateData['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($updateData['error']);
            return;
        }

        $this->showQueryDataDialog($editId, $rowIds, $updateData['fields']);
    }

    /**
     * @param int   $editId
     * @param array $rowIds
     * @param array $formValues
     *
     * @return void
     */
    public function save(int $editId, array $rowIds, array $formValues): void
    {
        if(!is_array($rowIds['where'] ?? 0) ||
            count($rowIds['where']) === 0 || $editId <= 0)
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Invalid query data');
            return;
        }

        // Add the select options, which are used to format the modified data
        $rowIds['select'] = $this->bag('dbadmin.select')->get('options', []);
        $result = $this->db()->updateItem($this->getTableName(), $rowIds, $formValues);
        // Show the error
        if(isset($result['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($result['error']);
            return;
        }
        // Show the warning
        if(isset($result['warning']))
        {
            $this->alert()
                ->title($this->trans()->lang('Warning'))
                ->warning($result['warning']);
            return;
        }

        // Update the result row.
        $this->cl(ResultRow::class)->renderItem($editId, $result);

        $this->modal()->hide();
        $this->alert()
            ->title($this->trans()->lang('Success'))
            ->success($result['message']);
    }

    /**
     * Back to the update form
     *
     * @param int $editId
     * @param array $rowIds
     * @param array $formValues
     *
     * @return void
     */
    public function showQueryForm(int $editId, array $rowIds, array $formValues): void
    {
        $tableName = $this->getTableName();
        // We need the table fields to be able to go back to the update form.
        $updateData = $this->db()->getUpdateData($tableName,  $rowIds);
        // Show the error
        if(isset($updateData['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($updateData['error']);
            return;
        }

        // Show the query in a modal dialog.
        $this->modal()->hide();

        $fields = $this->getEditedFormValues($updateData['fields'], $formValues);
        $this->showQueryDataDialog($editId, $rowIds, $fields);
    }

    /**
     * Show the update query
     *
     * @param int   $editId
     * @param array $rowIds
     * @param array $formValues
     *
     * @return void
     */
    public function showQueryCode(int $editId, array $rowIds, array $formValues): void
    {
        if(!is_array($rowIds['where'] ?? 0) ||
            count($rowIds['where']) === 0 || $editId <= 0)
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Invalid query data');
            return;
        }

        // Add the select options, which are used to format the modified data
        $rowIds['select'] = $this->bag('dbadmin.select')->get('options', []);
        $tableName = $this->getTableName();
        $result = $this->db()->getUpdateQuery($tableName, $rowIds, $formValues);
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
            'click' => $this->rq()->showQueryForm($editId, $rowIds, $formValues),
        ]];
        $this->showQueryCodeDialog('SQL query for update', $result['query'], $buttons);
    }
}

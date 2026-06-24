<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dml;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\ResultRow;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\SelectBagTrait;
use Lagdo\DbAdmin\App\Ui\Data\EditUiBuilder;

use function count;
use function is_array;
use function Jaxon\form;

/**
 * This class provides insert and update query features on tables.
 */
#[Databag('dbadmin.select')]
class UpdateFunc extends FuncComponent
{
    use SelectBagTrait;

    /**
     * @param EditUiBuilder  $editUi     The HTML UI builder
     */
    public function __construct(protected EditUiBuilder $editUi)
    {}

    /**
     * @param int $editId
     * @param array $rowIds
     * @param array $columns
     *
     * @return void
     */
    private function showQueryDataDialog(int $editId, array $rowIds, array $columns): void
    {
        $title = 'Edit row in table ' . $this->getCurrentTable();
        $content = $this->editUi->rowDataForm($columns);
        $values = form($this->editUi->queryFormId());
        // Add the select options, which are used to format the modified data
        $rowIds['select'] = $this->getSelectBag('options', []);

        // Bootbox options
        $options = ['size' => 'large'];
        $buttons = [[
            'title' => $this->trans()->lang('Cancel'),
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ], [
            'title' => $this->trans()->lang('Query'),
            'class' => 'btn btn-primary',
            'click' => $this->rq(SqlCodeFunc::class)
                ->showUpdateRowQuery($editId, $rowIds, $values),
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

        $updateData = $this->db()->getRowForUpdate($this->getCurrentTable(),  $rowIds);
        // Show the error
        if(isset($updateData['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($updateData['error']);
            return;
        }

        $this->showQueryDataDialog($editId, $rowIds, $updateData['columns']);
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
        $table = $this->getCurrentTable();
        $rowIds['select'] = $this->getSelectBag('options', []);
        $result = $this->db()->updateRow($table, $rowIds, $formValues);
        // Show the error
        if($result->error !== null)
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($result->error);
            return;
        }

        // Get the updated item.
        $updatedItem = $this->db()->getUpdatedRow($table, $rowIds, $formValues);
        if(isset($updatedItem['warning']))
        {
            $this->alert()
                ->title($this->trans()->lang('Warning'))
                ->warning($updatedItem['warning']);
            return;
        }

        // Update the result row.
        $this->cl(ResultRow::class)->renderItem($editId, $updatedItem);

        $this->modal()->hide();
        $this->alert()
            ->title($this->trans()->lang('Success'))
            ->success($result->message);
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
        $tableName = $this->getCurrentTable();
        // We need the table columns to be able to go back to the update form.
        $updateData = $this->db()->getRowForUpdate($tableName,  $rowIds);
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

        $columns = $this->getEditedFormValues($updateData['columns'], $formValues);
        $this->showQueryDataDialog($editId, $rowIds, $columns);
    }
}

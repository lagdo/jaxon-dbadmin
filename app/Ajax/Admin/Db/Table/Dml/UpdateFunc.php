<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dml;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder\QueryBuilderTrait;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\ResultRow;
use Lagdo\DbAdmin\App\Ui\Data\EditUiBuilder;

use function count;
use function is_array;
use function Jaxon\form;

/**
 * This class provides insert and update query features on tables.
 */
#[Databag('dbadmin.builder')]
class UpdateFunc extends FuncComponent
{
    use QueryBuilderTrait;

    /**
     * @param EditUiBuilder  $editUi     The HTML UI builder
     */
    public function __construct(protected EditUiBuilder $editUi)
    {}

    /**
     * @param int $rowId
     * @param array $rowIdValues
     * @param array $columns
     *
     * @return void
     */
    private function showQueryDataDialog(int $rowId, array $rowIdValues, array $columns): void
    {
        $title = 'Edit row in table ' . $this->getCurrentTable();
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
            'click' => $this->rq(SqlCodeFunc::class)
                ->showUpdateRowQuery($rowId, $rowIdValues, $values),
        ], [
            'title' => $this->trans()->lang('Update'),
            'class' => 'btn btn-primary',
            'click' => $this->rq()->save($rowId, $rowIdValues, $values)
                ->confirm($this->trans()->lang('Save this item?')),
        ]];

        $this->modal()->show($title, $content, $buttons, $options);
    }

    /**
     * @param int $rowId
     * @param array $rowIdValues
     *
     * @return void
     */
    public function edit(int $rowId, array $rowIdValues): void
    {
        if(!is_array($rowIdValues['where'] ?? 0) ||
            count($rowIdValues['where']) === 0 || $rowId <= 0)
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Invalid query data');
            return;
        }

        $updateData = $this->db()->getRowForUpdate($this->getCurrentTable(),  $rowIdValues);
        // Show the error
        if(isset($updateData['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($updateData['error']);
            return;
        }

        $this->showQueryDataDialog($rowId, $rowIdValues, $updateData['columns']);
    }

    /**
     * @param int   $rowId
     * @param array $rowIdValues
     * @param array $formValues
     *
     * @return void
     */
    public function save(int $rowId, array $rowIdValues, array $formValues): void
    {
        if(!is_array($rowIdValues['where'] ?? 0) ||
            count($rowIdValues['where']) === 0 || $rowId <= 0)
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Invalid query data');
            return;
        }

        $table = $this->getCurrentTable();
        $result = $this->db()->updateRow($table, $rowIdValues, $formValues);
        // Show the error
        if($result->error !== null)
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($result->error);
            return;
        }

        // Update the result row.
        $this->cl(ResultRow::class)->refreshItem($rowId, $rowIdValues);

        $this->modal()->hide();
        $this->alert()
            ->title($this->trans()->lang('Success'))
            ->success($result->message);
    }

    /**
     * Back to the update form
     *
     * @param int $rowId
     * @param array $rowIdValues
     * @param array $formValues
     *
     * @return void
     */
    public function showQueryForm(int $rowId, array $rowIdValues, array $formValues): void
    {
        $tableName = $this->getCurrentTable();
        // We need the table columns to be able to go back to the update form.
        $updateData = $this->db()->getRowForUpdate($tableName,  $rowIdValues);
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
        $this->showQueryDataDialog($rowId, $rowIdValues, $columns);
    }
}

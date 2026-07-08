<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dml;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder\QueryBuilderTrait;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\ResultRow;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\ResultSet;
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
     * @param int $uiRowId
     * @param array $dbRowIds
     * @param array $columns
     *
     * @return void
     */
    private function showDataUpdateDialog(int $uiRowId, array $dbRowIds, array $columns): void
    {
        $tableName = $this->getCurrentTable();
        $title = "Edit row in table $tableName";
        $content = $this->editUi->rowDataForm($tableName, $columns);
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
                ->showUpdateRowQuery($uiRowId, $dbRowIds, $values),
        ], [
            'title' => $this->trans()->lang('Update'),
            'class' => 'btn btn-primary',
            'click' => $this->rq()->save($uiRowId, $dbRowIds, $values)
                ->confirm($this->trans()->lang('Save this item?')),
        ]];

        $this->modal()->show($title, $content, $buttons, $options);
    }

    /**
     * @param int $uiRowId
     * @param array $dbRowIds
     *
     * @return void
     */
    public function edit(int $uiRowId, array $dbRowIds): void
    {
        if(!is_array($dbRowIds['where'] ?? 0) ||
            count($dbRowIds['where']) === 0 || $uiRowId <= 0)
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Invalid query data');
            return;
        }

        $updateData = $this->db()->getRowForUpdate($this->getCurrentTable(),  $dbRowIds);
        // Show the error
        if(isset($updateData['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($updateData['error']);
            return;
        }

        $this->showDataUpdateDialog($uiRowId, $dbRowIds, $updateData['columns']);
    }

    /**
     * @param int   $uiRowId
     * @param array $dbRowIds
     * @param array $rowValues
     *
     * @return void
     */
    public function save(int $uiRowId, array $dbRowIds, array $rowValues): void
    {
        if(!is_array($dbRowIds['where'] ?? 0) ||
            count($dbRowIds['where']) === 0 || $uiRowId <= 0)
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Invalid query data');
            return;
        }

        $table = $this->getCurrentTable();
        $result = $this->db()->updateRow($table, $dbRowIds, $rowValues);
        // Show the error
        if($result->error !== null)
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($result->error);
            return;
        }

        $newRowIdsKnown = true;
        foreach ($dbRowIds['where'] as $column => &$value) {
            if ($rowValues['input_values'][$column] !== $value) {
                if ($rowValues['input_functions'][$column] === '') {
                    // The row ids has changed to a known value.
                    $value = $rowValues['input_values'][$column];
                    continue;
                }
                // The row ids has changed to an unknown value.
                $newRowIdsKnown = false;
                break;
            }
        }

        $newRowIdsKnown ?
            // Refresh the result row.
            $this->cl(ResultRow::class)->refreshItem($uiRowId, $dbRowIds) :
            // Refresh all the result page.
            $this->cl(ResultSet::class)->page($this->getParamValue('page'));

        $this->modal()->hide();
        $this->alert()
            ->title($this->trans()->lang('Success'))
            ->success($result->message);
    }

    /**
     * Back to the update form
     *
     * @param int $uiRowId
     * @param array $dbRowIds
     * @param array $rowValues
     *
     * @return void
     */
    public function showQueryForm(int $uiRowId, array $dbRowIds, array $rowValues): void
    {
        $tableName = $this->getCurrentTable();
        // We need the table columns to be able to go back to the update form.
        $updateData = $this->db()->getRowForUpdate($tableName,  $dbRowIds);
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

        $columns = $this->getEditedFormValues($updateData['columns'], $rowValues);
        $this->showDataUpdateDialog($uiRowId, $dbRowIds, $columns);
    }
}

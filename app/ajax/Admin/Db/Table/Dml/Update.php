<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dml;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\ResultRow;

use function count;
use function is_array;
use function Jaxon\je;

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

        $queryData = $this->db()->getUpdateData($this->getTableName(),  $rowIds);
        // Show the error
        if(isset($queryData['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($queryData['error']);
            return;
        }

        $title = 'Edit row';
        $content = $this->editUi->rowDataForm($this->queryFormId, $queryData['fields'], '400px');
        // Bootbox options
        $options = ['size' => 'large'];
        $buttons = [[
            'title' => $this->trans()->lang('Cancel'),
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ], [
            'title' => $this->trans()->lang('Save'),
            'class' => 'btn btn-primary',
            'click' => $this->rq()->save($editId, $rowIds, je($this->queryFormId)->rd()->form())
                ->confirm($this->trans()->lang('Save this item?')),
        ]];
        $this->modal()->show($title, $content, $buttons, $options);
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
}

<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dml;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\ResultRow;
use Lagdo\DbAdmin\Ajax\App\Db\Table\FuncComponent;

use function count;
use function Jaxon\je;

/**
 * This class provides insert and update query features on tables.
 */
#[Databag('dbadmin.select')]
#[Databag('dbadmin.row.edit')]
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
     *
     * @return void
     */
    public function edit(int $editId): void
    {
        $rowIds = $this->bag('dbadmin.row.edit')->get('row.ids', []);
        if(!isset($rowIds[$editId]) || count($rowIds[$editId]['where']) === 0)
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Invalid query data');
            return;
        }

        $queryData = $this->db()->getQueryData($this->getTableName(), $rowIds[$editId]);
        // Show the error
        if(isset($queryData['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($queryData['error']);
            return;
        }

        $title = 'Edit row';
        $content = $this->tableUi->queryForm($queryData['fields'], '500px');
        // Bootbox options
        $options = ['size' => 'large'];
        $buttons = [[
            'title' => $this->trans()->lang('Cancel'),
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ], [
            'title' => $this->trans()->lang('Save'),
            'class' => 'btn btn-primary',
            'click' => $this->rq()->save(je($this->queryFormId)->rd()->form())
                ->confirm($this->trans()->lang('Save this item?')),
        ]];
        $this->modal()->show($title, $content, $buttons, $options);
    }

    /**
     * @param int   $editId
     * @param array $formValues
     *
     * @return void
     */
    public function save(int $editId, array $formValues): void
    {
        $rowIds = $this->bag('dbadmin.row.edit')->get('row.ids', []);
        if(!isset($rowIds[$editId]) || count($rowIds[$editId]['where']) === 0)
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Invalid query data');
            return;
        }

        $results = $this->db()->updateItem($this->getTableName(), $formValues);
        // Show the error
        if(isset($results['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($results['error']);
            return;
        }

        // Update the result row.
        // $this->cl(ResultRow::class)->item($editId)->render();

        $this->modal()->hide();
        $this->alert()
            ->title($this->trans()->lang('Success'))
            ->success($results['message']);
    }
}

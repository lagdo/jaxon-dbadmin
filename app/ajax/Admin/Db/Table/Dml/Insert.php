<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dml;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\ResultSet;

use function Jaxon\je;

/**
 * This class provides insert and update query features on tables.
 */
#[Databag('dbadmin.select')]
#[Databag('dbadmin.row.edit')]
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
     *
     * @return void
     */
    public function show(bool $fromSelect): void
    {
        $tableName = $this->getTableName();
        $insertData = $this->db()->getInsertData($tableName);
        // Show the error
        if(isset($insertData['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($insertData['error']);
            return;
        }

        $title = "New item in table $tableName";
        $content = $this->editUi->rowDataForm($this->queryFormId, $insertData['fields'], '400px');
        // Bootbox options
        $options = ['size' => 'large'];
        $buttons = [[
            'title' => $this->trans()->lang('Cancel'),
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ], [
            'title' => $this->trans()->lang('Save'),
            'class' => 'btn btn-primary',
            'click' => $this->rq()->save($fromSelect, je($this->queryFormId)->rd()->form())
                ->confirm($this->trans()->lang('Save this item?')),
        ]];
        $this->modal()->show($title, $content, $buttons, $options);
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
}

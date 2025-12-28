<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dml;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\ResultSet;

use function count;
use function is_array;

/**
 * This class provides insert and update query features on tables.
 */
#[Databag('dbadmin.select')]
class Delete extends FuncComponent
{
    /**
     * Execute the delete query
     *
     * @param int   $editId
     * @param array $rowIds
     *
     * @return void
     */
    public function exec(int $editId, array $rowIds): void
    {
        if(!is_array($rowIds['where'] ?? 0) ||
            count($rowIds['where']) === 0 || $editId <= 0)
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Invalid query data');
            return;
        }

        $result = $this->db()->deleteItem($this->getTableName(), $rowIds);
        // Show the error
        if(isset($result['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($result['error']);
            return;
        }

        // Refresh the result set.
        $this->cl(ResultSet::class)->page();

        $this->modal()->hide();
        $this->alert()
            ->title($this->trans()->lang('Success'))
            ->success($result['message']);
    }

    /**
     * Show the delete query
     *
     * @param int   $editId
     * @param array $rowIds
     *
     * @return void
     */
    public function showQueryCode(int $editId, array $rowIds): void
    {
        if(!is_array($rowIds['where'] ?? 0) ||
            count($rowIds['where']) === 0 || $editId <= 0)
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Invalid query data');
            return;
        }

        $result = $this->db()->getDeleteQuery($this->getTableName(), $rowIds);
        // Show the error
        if(isset($result['error']))
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($result['error']);
            return;
        }

        // Show the query in a modal dialog.
        $this->showQueryCodeDialog('SQL query for delete', $result['query']);
    }
}

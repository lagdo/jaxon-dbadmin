<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dml;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\ResultSet;
use Lagdo\DbAdmin\Ajax\App\Db\Table\FuncComponent;

use function count;

/**
 * This class provides insert and update query features on tables.
 */
#[Databag('dbadmin.select')]
#[Databag('dbadmin.row.edit')]
class Delete extends FuncComponent
{
    /**
     * Execute the delete query
     *
     * @param int   $editId
     *
     * @return void
     */
    public function exec(int $editId): void
    {
        $rowIds = $this->bag('dbadmin.row.edit')->get('row.ids', []);
        if(!isset($rowIds[$editId]) || count($rowIds[$editId]['where']) === 0)
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Invalid query data');
            return;
        }

        $queryOptions = $rowIds[$editId];
        $result = $this->db()->deleteItem($this->getTableName(), $queryOptions);
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
}

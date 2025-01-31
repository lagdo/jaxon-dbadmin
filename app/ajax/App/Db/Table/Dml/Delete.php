<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dml;

use Lagdo\DbAdmin\Ajax\App\Db\Table\CallableClass;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Select;

/**
 * This class provides insert and update query features on tables.
 */
class Delete extends CallableClass
{
    /**
     * Execute the delete query
     *
     * @databag('name' => 'dbadmin.select')
     * @after('call' => 'debugQueries')
     *
     * @param array  $rowIds        The row identifiers
     *
     * @return void
     */
    public function exec(array $rowIds)
    {
        $results = $this->db()->deleteItem($this->getTableName(), $rowIds);

        // Show the error
        if(($results['error']))
        {
            $this->alert()->title($this->trans()->lang('Error'))->error($results['error']);
            return;
        }
        $this->alert()->title($this->trans()->lang('Success'))->success($results['message']);
        $this->rq(Select::class)->exec();
    }
}

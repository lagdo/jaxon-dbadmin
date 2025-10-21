<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dml;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\App\Db\Table\FuncComponent;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Select;

/**
 * This class provides insert and update query features on tables.
 */
#[Before('notYetAvailable')]
class Delete extends FuncComponent
{
    /**
     * Execute the delete query
     *
     * @param array  $rowIds        The row identifiers
     *
     * @return void
     */
    #[Databag('dbadmin.select')]
    public function exec(array $rowIds): void
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

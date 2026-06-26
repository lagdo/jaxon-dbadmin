<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dml;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\ResultSet;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\SelectBagTrait;

use function count;
use function is_array;

/**
 * This class provides insert and update query features on tables.
 */
#[Databag('dbadmin.select')]
class DeleteFunc extends FuncComponent
{
    use SelectBagTrait;

    /**
     * Execute the delete query
     *
     * @param int   $rowId
     * @param array $rowIdValues
     *
     * @return void
     */
    public function exec(int $rowId, array $rowIdValues): void
    {
        if(!is_array($rowIdValues['where'] ?? 0) ||
            count($rowIdValues['where']) === 0 || $rowId <= 0)
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Invalid query data');
            return;
        }

        $result = $this->db()->deleteRow($this->getCurrentTable(), $rowIdValues);
        // Show the error
        if($result->error !== null)
        {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error($result->error);
            return;
        }

        // Refresh the result set.
        $this->cl(ResultSet::class)->page();

        $this->modal()->hide();
        $this->alert()
            ->title($this->trans()->lang('Success'))
            ->success($result->message);
    }
}

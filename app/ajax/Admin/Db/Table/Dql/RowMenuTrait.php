<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;

use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dml\Delete;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dml\Update;

use function Jaxon\jo;

trait RowMenuTrait
{
    /**
     * @param int $editId
     *
     * @return string
     */
    protected function bagEntryName(int $editId): string
    {
        return "row$editId";
    }

    /**
     * @param int $editId
     *
     * @return string
     */
    protected function getRowMenu(int $editId): string
    {
        $bagEntryValue = jo('jaxon')
            ->bag('dbadmin.row.edit', 'row.ids', $this->bagEntryName($editId), null);
        return $this->ui()->tableMenu([[
            'label' => $this->trans->lang('Edit'),
            'handler' => $this->rq(Update::class)->edit($editId, $bagEntryValue),
        ], [
            'label' => $this->trans->lang('Delete'),
            'handler' => $this->rq(Delete::class)->exec($editId, $bagEntryValue)
                ->confirm($this->trans()->lang('Delete this item?')),
        ]]);
    }
}

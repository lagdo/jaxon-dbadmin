<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dml\DeleteFunc;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dml\UpdateFunc;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dml\SqlCodeFunc;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\QueryResultRowDto;

use function count;

trait RowMenuTrait
{
    /**
     * @param int $rowId
     *
     * @return string
     */
    protected function bagValueKey(int $rowId): string
    {
        return "row$rowId";
    }

    /**
     * @param int $rowId
     * @param QueryResultRowDto $row
     *
     * @return string
     */
    protected function getRowMenu(int $rowId, QueryResultRowDto $row): string
    {
        if ($row->editValues === null) {
            return '';
        }

        $editCount = count($row->editValues ?? []);
        $nullCount = count($row->nullValues ?? []);
        if ($editCount === 0 && $nullCount === 0) {
            return '';
        }

        $rowIdValues = [];
        if ($editCount > 0) {
            $rowIdValues['where'] = $row->editValues;
        }
        if ($nullCount > 0) {
            $rowIdValues['null'] = $row->nullValues;
        }

        return $this->ui()->buttonMenu([[
            'label' => $this->trans->lang('Edit'),
            'handler' => $this->rq(UpdateFunc::class)->edit($rowId, $rowIdValues),
        ], [
            'label' => $this->trans->lang('Delete'),
            'handler' => $this->rq(DeleteFunc::class)->exec($rowId, $rowIdValues)
                ->confirm($this->trans()->lang('Delete this item?')),
        ], [
            'label' => $this->trans->lang('Delete query'),
            'handler' => $this->rq(SqlCodeFunc::class)->showDeleteRowQuery($rowId, $rowIdValues),
        ]]);
    }
}

<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ui\Select\ResultUiBuilder;

/**
 * This class displays a row of a select query rowset.
 */
#[Exclude]
class ResultRow extends Component
{
    use RowMenuTrait;

    /**
     * @param ResultUiBuilder   $resultUi   The HTML UI builder
     */
    public function __construct(protected ResultUiBuilder $resultUi)
    {}

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->resultUi->resultRowContent($this->get('rowset'));
    }

    /**
     * @param int $rowId
     * @param array $rowIdValues
     *
     * @return void
     */
    public function refreshItem(int $rowId, array $rowIdValues): void
    {
        // Set the options to fetch only the updated item.
        $select = $this->select(['page' => 0, 'limit' => 1]);
        $columns = $select->table->columns;
        $select->filters[] = $this->driver()->getSelectWhereClause($rowIdValues, $columns);

        $result = $this->driver()->execSelect($select);
        if ($result->error !== null) {
            $this->alert()
                ->title($this->trans()->lang('Warning'))
                ->warning($result->error);
            return;
        }

        $rowset = $result->rowsets[0] ?? null;
        if ($rowset === null || !isset($rowset?->rows[0])) {
            $this->alert()
                ->title($this->trans()->lang('Warning'))
                ->warning($this->trans()->lang('Unable to read the updated row.'));
            return;
        }

        $rowset->rows[0]->bagId = $this->bagValueKey($rowId);
        $rowset->rows[0]->rowMenu = $this->getRowMenu($rowId, $rowset->rows[0]);
        $this->set('rowset', $rowset);

        $this->item($this->bagValueKey($rowId))->render();
    }
}

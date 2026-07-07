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
        return $this->resultUi->resultRowContent($this->get('row'));
    }

    /**
     * @param int $rowId
     * @param array $rowIdValues
     *
     * @return void
     */
    public function refreshItem(int $rowId, array $rowIdValues): void
    {
        $params = $this->getBuilderParams();
        // Set the options to fetch pnly the updated item.
        $params['page'] = 0;
        $params['limit'] = 1;
        $params['updated'] = $rowIdValues;
        $select = $this->db()->getSelectParams($this->getCurrentTable(), $params);

        $result = $this->db()->execSelect($select);

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

        if ((bool)$this->getParamValue('foreigns')) {
            $textLength = $this->getParamValue('length');
            if ($this->db()->setForeignKeyLabels($rowset, $textLength) === 0) {
                $this->saveParamValue('foreigns', false);
            }
        }

        $row = $rowset->rows[0];
        $row->bagId = $this->bagValueKey($rowId);
        $row->rowMenu = $this->getRowMenu($rowId, $row);
        $this->set('row', $row);

        $this->item($this->bagValueKey($rowId))->render();
    }
}

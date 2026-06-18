<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ui\Select\ResultUiBuilder;
use Lagdo\DbAdmin\App\Ajax\Base\Component;

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
     * @param int $editId
     * @param array $row
     *
     * @return void
     */
    public function renderItem(int $editId, array $row): void
    {
        $row['editId'] = $editId;
        $row['menu'] = $this->getRowMenu($editId);
        $this->set('row', $row);

        $this->item($this->bagValueKey($editId))->render();
    }
}

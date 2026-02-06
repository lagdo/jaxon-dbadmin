<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ui\Select\ResultUiBuilder;

/**
 * This class displays a row of a select query resultset.
 */
#[Exclude]
class ResultRow extends MainComponent
{
    use RowMenuTrait;

    /**
     * @var string
     */
    protected string $overrides = '';

    /**
     * @var array
     */
    private $row = [];

    /**
     * The constructor
     *
     * @param ResultUiBuilder   $resultUi   The HTML UI builder
     */
    public function __construct(protected ResultUiBuilder $resultUi)
    {}

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->resultUi->resultRowContent($this->row);
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
        $this->row = $row;

        $this->item($this->bagValueKey($editId))->render();
    }
}

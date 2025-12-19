<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;

use Jaxon\Attributes\Attribute\Exclude;

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
    protected $overrides = '';

    /**
     * @var array
     */
    private $row = [];

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->selectUi->resultRowContent($this->row);
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

        $this->item($this->bagEntryName($editId))->render();
    }
}

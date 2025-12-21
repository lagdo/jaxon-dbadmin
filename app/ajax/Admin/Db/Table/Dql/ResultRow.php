<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Select\ResultUiBuilder;
use Lagdo\DbAdmin\Ui\UiBuilder;

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
     * The constructor
     *
     * @param UiBuilder         $ui         The HTML UI builder
     * @param ResultUiBuilder   $resultUi   The HTML UI builder
     * @param Translator        $trans
     */
    public function __construct(protected UiBuilder $ui,
        protected ResultUiBuilder $resultUi, protected Translator $trans)
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

        $this->item($this->bagEntryName($editId))->render();
    }
}

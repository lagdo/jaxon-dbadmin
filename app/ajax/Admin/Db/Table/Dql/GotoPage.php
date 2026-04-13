<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;

use Jaxon\App\ComponentDataTrait;
use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\Base\Component;
use Lagdo\DbAdmin\Ui\Select\SelectUiBuilder;

/**
 * This component displays the SQL query duration.
 */
#[Exclude]
class GotoPage extends Component
{
    use ComponentDataTrait;

    /**
     * @param SelectUiBuilder $selectUi
     */
    public function __construct(private SelectUiBuilder $selectUi)
    {}

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->selectUi->gotoPageForm($this->get('page', 0));
    }
}

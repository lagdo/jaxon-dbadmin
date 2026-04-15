<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql;

use Jaxon\App\ComponentDataTrait;
use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ajax\Base\Component;
use Lagdo\DbAdmin\App\Ui\Select\SelectUiBuilder;

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

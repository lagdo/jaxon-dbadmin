<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql;

use Jaxon\App\ComponentDataTrait;
use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ajax\Base\Component;
use Lagdo\DbAdmin\App\Ui\Select\SelectUiBuilder;

/**
 * This component displays foreign fields loading switch.
 */
#[Exclude]
class Foreigns extends Component
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
        return $this->selectUi->toggleForeigns($this->get('loaded', false));
    }
}

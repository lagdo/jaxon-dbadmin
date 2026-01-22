<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\Base\Component;
use Lagdo\DbAdmin\Ui\Select\SelectUiBuilder;

use function is_float;

/**
 * This component displays the SQL query duration.
 */
#[Exclude]
class Duration extends Component
{
    /**
     * @param SelectUiBuilder $selectUi The HTML UI builder
     */
    public function __construct(protected SelectUiBuilder $selectUi)
    {}

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $duration = $this->stash()->get('select.duration', null);
        return is_float($duration) ? $this->selectUi->duration($duration) : '';
    }
}

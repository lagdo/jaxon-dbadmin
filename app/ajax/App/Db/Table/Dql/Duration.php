<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dql;

use Lagdo\DbAdmin\Ajax\Component;
use Lagdo\DbAdmin\Ui\Table\SelectUiBuilder;

use function is_float;

/**
 * This component displays the SQL query duration.
 *
 * @exclude
 */
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

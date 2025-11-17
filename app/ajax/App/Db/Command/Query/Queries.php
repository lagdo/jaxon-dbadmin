<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Command\Query;

use Lagdo\DbAdmin\Ajax\Component;
use Lagdo\DbAdmin\Ui\Command\LogUiBuilder;

/**
 * User queries container (history and favorites)
 */
class Queries extends Component
{
    /**
     * @param LogUiBuilder $logUi
     */
    public function __construct(private LogUiBuilder $logUi)
    {}

    /**
     * @return string
     */
    public function html(): string
    {
        return $this->logUi->queries();
    }
}
